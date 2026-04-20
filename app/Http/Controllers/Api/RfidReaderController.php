<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRfidReaderRequest;
use App\Http\Requests\UpdateRfidReaderRequest;
use App\Models\RfidReader;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API Controller for RFID Reader Management
 */
class RfidReaderController extends Controller
{
    /**
     * GET /api/rfid/readers
     * List all RFID readers
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id' => 'sometimes|integer|exists:warehouses,id',
            'status' => 'sometimes|in:active,inactive,maintenance',
            'page' => 'sometimes|integer',
            'per_page' => 'sometimes|integer|max:100',
        ]);

        $query = RfidReader::with('warehouse');

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $readers = $query->paginate($request->input('per_page', 50));

        return response()->json([
            'status' => 'success',
            'data' => $readers,
        ]);
    }

    /**
     * POST /api/rfid/readers
     * Create a new RFID reader
     */
    public function store(StoreRfidReaderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $reader = RfidReader::create([
            'name' => $validated['name'],
            'ip_address' => $validated['ip_address'],
            'warehouse_id' => $validated['warehouse_id'],
            'location' => $validated['location'],
            'status' => $validated['status'] ?? 'active',
            'read_range' => $validated['read_range'] ?? 5,
            'frequency' => $validated['frequency'] ?? null,
            'protocol' => $validated['protocol'] ?? 'TCP',
            'port' => $validated['port'] ?? 9096,
            'account_id' => auth()->user()->account_id,
        ]);

        $reader->load('warehouse');

        return response()->json([
            'status' => 'success',
            'message' => 'RFID reader created',
            'data' => $reader,
        ], 201);
    }

    /**
     * GET /api/rfid/readers/{reader}
     * Get reader details
     */
    public function show(RfidReader $reader): JsonResponse
    {
        $reader->load('warehouse', 'scans');

        return response()->json([
            'status' => 'success',
            'data' => $reader,
        ]);
    }

    /**
     * PUT /api/rfid/readers/{reader}
     * Update reader
     */
    public function update(UpdateRfidReaderRequest $request, RfidReader $reader): JsonResponse
    {
        $validated = $request->validated();

        $reader->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'RFID reader updated',
            'data' => $reader,
        ]);
    }

    /**
     * DELETE /api/rfid/readers/{reader}
     * Delete reader
     */
    public function destroy(RfidReader $reader): JsonResponse
    {
        $this->authorize('delete', $reader);

        $reader->forceDelete();

        return response()->json([
            'status' => 'success',
            'message' => 'RFID reader deleted',
        ]);
    }

    /**
     * POST /api/rfid/readers/{reader}/health
     * Check reader health/connectivity
     */
    public function checkHealth(RfidReader $reader): JsonResponse
    {
        // Implementation would depend on actual reader protocol
        // This is a placeholder for testing connectivity

        return response()->json([
            'status' => 'success',
            'data' => [
                'reader_id' => $reader->id,
                'name' => $reader->name,
                'ip_address' => $reader->ip_address,
                'is_online' => true,
                'last_scan' => $reader->scans()->latest()->first()?->created_at,
                'signal_strength' => 95,
            ],
        ]);
    }

    /**
     * GET /api/rfid/readers/{reader}/scans
     * Get recent scans from reader
     */
    public function getReaderScans(Request $request, RfidReader $reader): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'days' => 'sometimes|integer|min:1|max:90',
        ]);

        $query = $reader->scans();

        if ($request->has('days')) {
            $query->where('created_at', '>=', now()->subDays($request->input('days')));
        }

        $scans = $query->latest('created_at')
            ->limit($request->input('limit', 100))
            ->get();

        return response()->json([
            'status' => 'success',
            'count' => $scans->count(),
            'data' => $scans,
        ]);
    }

    /**
     * PUT /api/rfid/readers/{reader}/status
     * Update reader status
     */
    public function updateStatus(Request $request, RfidReader $reader): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,inactive,maintenance',
        ]);

        $reader->update([
            'status' => $request->input('status'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Reader status updated',
            'data' => $reader,
        ]);
    }
}
