<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\RfidTag;
use App\Models\RfidReader;
use App\Models\RfidScanLog;
use App\Models\ItemLocation;
use App\Models\Warehouse;
use App\Models\RfidTagAssignment;
use App\Services\Rfid\RfidScanProcessor;
use App\Http\Requests\StoreRfidTagRequest;
use App\Http\Requests\UpdateRfidTagRequest;
use App\Http\Requests\StoreRfidReaderRequest;
use App\Http\Requests\UpdateRfidReaderRequest;
use App\Http\Requests\StoreRfidTagAssignmentRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;

/**
 * Controller for RFID Web UI (Inertia/Vue)
 * Handles page rendering for RFID management
 */
class RfidController extends Controller
{
    /**
     * RFID Dashboard - Live scan monitor
     */
    public function live(Request $request)
    {
        $warehouses = Warehouse::ofAccount()->active()->get();
        $selectedWarehouse = $request->input('warehouse') ?? $warehouses->first()?->id;

        $recentScans = RfidScanLog::with(['item', 'reader', 'warehouse'])
            ->where('warehouse_id', $selectedWarehouse)
            ->where('created_at', '>=', now()->subHours(24))
            ->latest('created_at')
            ->limit(100)
            ->get();

        $summary = [
            'total_today' => RfidScanLog::where('warehouse_id', $selectedWarehouse)
                ->whereDate('created_at', today())
                ->count(),
            'scans_by_action' => RfidScanLog::where('warehouse_id', $selectedWarehouse)
                ->whereDate('created_at', today())
                ->groupBy('action')
                ->selectRaw('action, count(*) as count')
                ->pluck('count', 'action')
                ->toArray(),
            'success_rate' => $this->getSuccessRate($selectedWarehouse),
        ];

        $readers = RfidReader::where('warehouse_id', $selectedWarehouse)
            ->active()
            ->get();

        return Inertia::render('Rfid/Live', [
            'warehouses' => $warehouses,
            'selectedWarehouse' => $selectedWarehouse,
            'recentScans' => $recentScans,
            'summary' => $summary,
            'readers' => $readers,
        ]);
    }

    /**
     * RFID Tag Assignment Page
     */
    public function assign(Request $request)
    {
        $warehouses = Warehouse::ofAccount()->active()->get();

        $tags = RfidTag::with('assignment.item')
            ->ofAccount()
            ->paginate(50);

        $items = Item::where('account_id', auth()->user()->account_id)
            ->select('id', 'name', 'code', 'sku')
            ->orderBy('name')
            ->get();

        return Inertia::render('Rfid/TagAssignment', [
            'tags' => $tags,
            'items' => $items,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Store tag assignment
     */
    public function storeAssign(StoreRfidTagAssignmentRequest $request)
    {
        $validated = $request->validated();

        $tag = RfidTag::findOrFail($validated['rfid_tag_id']);
        $item = Item::findOrFail($validated['item_id']);

        // Check if tag already assigned
        if ($tag->assignment) {
            return back()->withErrors(['tag' => 'Tag already assigned to another item']);
        }

        // Check if item already has assignment
        $existing = $item->rfidAssignment;
        if ($existing) {
            $existing->update(['status' => 'reassigned']);
            $existing->delete();
        }

        RfidTagAssignment::create([
            'rfid_tag_id' => $tag->id,
            'item_id' => $item->id,
            'account_id' => auth()->user()->account_id,
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
            'status' => 'active',
        ]);

        $tag->update(['status' => 'active']);

        return back()->with('success', "Tag assigned to {$item->name}");
    }

    /**
     * RFID Scan Logs Viewer
     */
    public function logs(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'sometimes|integer',
            'action' => 'sometimes|in:IN,OUT,MOVE,TRANSFER',
            'days' => 'sometimes|integer|min:1|max:90',
        ]);

        $warehouses = Warehouse::ofAccount()->active()->get();
        $selectedWarehouse = $request->input('warehouse_id') ?? $warehouses->first()?->id;

        $query = RfidScanLog::with(['item', 'reader', 'warehouse'])
            ->where('warehouse_id', $selectedWarehouse)
            ->where('status', '!=', 'duplicate');

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->has('days')) {
            $query->where('created_at', '>=', now()->subDays($request->input('days')));
        }

        $logs = $query->latest('created_at')->paginate(50);

        return Inertia::render('Rfid/Logs', [
            'logs' => $logs,
            'warehouses' => $warehouses,
            'selectedWarehouse' => $selectedWarehouse,
            'filters' => $request->all(),
        ]);
    }

    /**
     * RFID Readers Management Page
     */
    public function readers(Request $request)
    {
        $warehouses = Warehouse::ofAccount()->active()->get();
        $selectedWarehouse = $request->input('warehouse_id') ?? $warehouses->first()?->id;

        $readers = RfidReader::with('warehouse')
            ->where('warehouse_id', $selectedWarehouse)
            ->latest('created_at')
            ->paginate(20);

        $summary = [
            'total_readers' => RfidReader::where('warehouse_id', $selectedWarehouse)->count(),
            'active_readers' => RfidReader::where('warehouse_id', $selectedWarehouse)
                ->where('status', 'active')
                ->count(),
            'inactive_readers' => RfidReader::where('warehouse_id', $selectedWarehouse)
                ->where('status', 'inactive')
                ->count(),
        ];

        return Inertia::render('Rfid/Readers', [
            'readers' => $readers,
            'warehouses' => $warehouses,
            'selectedWarehouse' => $selectedWarehouse,
            'summary' => $summary,
        ]);
    }

    /**
     * RFID Tags Management Page
     */
    public function tags(Request $request)
    {
        $request->validate([
            'status' => 'sometimes|in:active,inactive,lost,damaged',
            'assigned' => 'sometimes|in:yes,no',
        ]);

        $query = RfidTag::with(['assignment.item'])
            ->ofAccount();

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('assigned')) {
            if ($request->input('assigned') === 'yes') {
                $query->whereHas('assignment');
            } else {
                $query->doesntHave('assignment');
            }
        }

        $tags = $query->latest('created_at')->paginate(50);

        $summary = [
            'total_tags' => RfidTag::ofAccount()->count(),
            'assigned_tags' => RfidTag::ofAccount()->whereHas('assignment')->count(),
            'unassigned_tags' => RfidTag::ofAccount()->doesntHave('assignment')->count(),
            'active_tags' => RfidTag::ofAccount()->where('status', 'active')->count(),
        ];

        return Inertia::render('Rfid/Tags', [
            'tags' => $tags,
            'summary' => $summary,
            'filters' => $request->all(),
        ]);
    }

    /**
     * Store new RFID tag
     */
    public function storeTag(StoreRfidTagRequest $request)
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

        return back()->with('success', "RFID tag {$tag->tag_code} created successfully");
    }

    /**
     * Update RFID tag
     */
    public function updateTag(UpdateRfidTagRequest $request, RfidTag $tag)
    {
        $validated = $request->validated();

        $tag->update($validated);

        return back()->with('success', "RFID tag updated successfully");
    }

    /**
     * Delete RFID tag
     */
    public function destroyTag(RfidTag $tag)
    {
        $this->authorize('delete', $tag);

        if ($tag->assignment) {
            return back()->withErrors(['tag' => 'Cannot delete assigned tag. Unassign first.']);
        }

        $tag->delete();

        return back()->with('success', 'RFID tag deleted successfully');
    }

    /**
     * Store new RFID reader
     */
    public function storeReader(StoreRfidReaderRequest $request)
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

        return back()->with('success', "RFID reader {$reader->name} created successfully");
    }

    /**
     * Update RFID reader
     */
    public function updateReader(UpdateRfidReaderRequest $request, RfidReader $reader)
    {
        $validated = $request->validated();

        $reader->update($validated);

        return back()->with('success', 'RFID reader updated successfully');
    }

    /**
     * Delete RFID reader
     */
    public function destroyReader(RfidReader $reader)
    {
        $this->authorize('delete', $reader);

        $reader->forceDelete();

        return back()->with('success', 'RFID reader deleted successfully');
    }

    /**
     * Unassign tag from item
     */
    public function unassignTag(RfidTag $tag)
    {
        if (!$tag->assignment) {
            return back()->withErrors(['tag' => 'Tag is not assigned to any item']);
        }

        $itemName = $tag->assignment->item->name ?? 'Unknown Item';
        $tag->assignment->update(['status' => 'inactive']);
        $tag->assignment->delete();
        $tag->update(['status' => 'inactive']);

        return back()->with('success', "Tag unassigned from {$itemName}");
    }

    /**
     * Calculate success rate for scans
     */
    private function getSuccessRate($warehouseId): float
    {
        $total = RfidScanLog::where('warehouse_id', $warehouseId)
            ->whereDate('created_at', today())
            ->count();

        if ($total === 0) return 0;

        $successful = RfidScanLog::where('warehouse_id', $warehouseId)
            ->where('status', 'processed')
            ->whereDate('created_at', today())
            ->count();

        return round(($successful / $total) * 100, 2);
    }
}
