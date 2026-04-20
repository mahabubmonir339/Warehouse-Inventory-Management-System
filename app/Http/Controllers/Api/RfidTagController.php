<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRfidTagRequest;
use App\Http\Requests\UpdateRfidTagRequest;
use App\Http\Requests\StoreRfidTagAssignmentRequest;
use App\Models\RfidTag;
use App\Models\RfidTagAssignment;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API Controller for RFID Tag Management
 */
class RfidTagController extends Controller
{
    /**
     * GET /api/rfid/tags
     * List all RFID tags
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'sometimes|in:active,inactive,lost,damaged',
            'unassigned' => 'sometimes|boolean',
            'page' => 'sometimes|integer',
            'per_page' => 'sometimes|integer|max:100',
        ]);

        $query = RfidTag::with('assignment.item');

        if ($request->input('unassigned')) {
            $query->whereDoesntHave('assignment');
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $tags = $query->paginate($request->input('per_page', 50));

        return response()->json([
            'status' => 'success',
            'data' => $tags,
        ]);
    }

    /**
     * POST /api/rfid/tags
     * Create a new RFID tag
     */
    public function store(StoreRfidTagRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $tag = RfidTag::create([
            'tag_code' => $validated['tag_code'],
            'tag_type' => $validated['tag_type'] ?? 'UHF',
            'status' => $validated['status'] ?? 'active',
            'frequency' => $validated['frequency'] ?? null,
            'epc' => $validated['epc'] ?? null,
            'is_active' => true,
            'account_id' => auth()->user()->account_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'RFID tag created',
            'data' => $tag,
        ], 201);
    }

    /**
     * GET /api/rfid/tags/{tag}
     * Get tag details
     */
    public function show(RfidTag $tag): JsonResponse
    {
        $tag->load('assignment.item', 'scans');

        return response()->json([
            'status' => 'success',
            'data' => $tag,
        ]);
    }

    /**
     * PUT /api/rfid/tags/{tag}
     * Update tag
     */
    public function update(UpdateRfidTagRequest $request, RfidTag $tag): JsonResponse
    {
        $validated = $request->validated();

        $tag->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'RFID tag updated',
            'data' => $tag,
        ]);
    }

    /**
     * DELETE /api/rfid/tags/{tag}
     * Delete tag
     */
    public function destroy(RfidTag $tag): JsonResponse
    {
        $this->authorize('delete', $tag);

        if ($tag->assignment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete assigned tag. Unassign first.',
            ], 422);
        }

        $tag->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'RFID tag deleted',
        ]);
    }

    /**
     * POST /api/rfid/tags/{tag}/assign
     * Assign tag to item
     */
    public function assignToItem(StoreRfidTagAssignmentRequest $request, RfidTag $tag): JsonResponse
    {
        $validated = $request->validated();
        $item = Item::findOrFail($validated['item_id']);

        // Check if tag already assigned
        if ($tag->assignment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tag already assigned',
            ], 422);
        }

        // Check if item already has a tag
        $existingAssignment = RfidTagAssignment::where('item_id', $item->id)->first();
        if ($existingAssignment) {
            // Optionally allow reassignment
            $existingAssignment->update([
                'status' => 'reassigned',
            ]);
        }

        $assignment = RfidTagAssignment::create([
            'rfid_tag_id' => $tag->id,
            'item_id' => $item->id,
            'account_id' => auth()->user()->account_id,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $tag->update(['status' => 'active']);

        return response()->json([
            'status' => 'success',
            'message' => 'Tag assigned to item',
            'data' => $assignment->load('tag', 'item'),
        ], 201);
    }

    /**
     * POST /api/rfid/tags/{tag}/unassign
     * Unassign tag from item
     */
    public function unassignFromItem(RfidTag $tag): JsonResponse
    {
        if (!$tag->assignment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tag not assigned',
            ], 422);
        }

        $tag->assignment->update(['status' => 'inactive']);
        $tag->assignment->delete();
        $tag->update(['status' => 'inactive']);

        return response()->json([
            'status' => 'success',
            'message' => 'Tag unassigned',
        ]);
    }

    /**
     * POST /api/rfid/tags/bulk-create
     * Create multiple tags
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $request->validate([
            'tags' => 'required|array|min:1',
            'tags.*.tag_code' => 'required|string|unique:rfid_tags',
            'tags.*.tag_type' => 'sometimes|string',
        ]);

        $created = [];
        foreach ($request->input('tags') as $tagData) {
            $created[] = RfidTag::create([
                'tag_code' => $tagData['tag_code'],
                'tag_type' => $tagData['tag_type'] ?? 'UHF',
                'is_active' => true,
                'status' => 'active',
                'account_id' => auth()->user()->account_id,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tags created',
            'count' => count($created),
            'data' => $created,
        ], 201);
    }
}
