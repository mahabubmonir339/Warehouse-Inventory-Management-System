<?php

namespace App\Services\Rfid;

use App\Models\Item;
use App\Models\RfidTag;
use App\Models\RfidReader;
use App\Models\RfidScanLog;
use App\Models\ItemLocation;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Core RFID Scan Processing Service
 * Handles RFID tag detection, action resolution, and integration with inventory system
 */
class RfidScanProcessor
{
    const DUPLICATE_COOLDOWN = 2; // seconds - prevent duplicate scans
    const ACTIONS = ['IN', 'OUT', 'MOVE', 'TRANSFER'];

    /**
     * Process an RFID scan from a reader
     *
     * @param string $tagUid Tag UID from reader
     * @param int $readerId ID of the RFID reader
     * @param array $metadata Optional metadata about the scan
     * @return array Result with status and message
     */
    public function processScan(string $tagUid, int $readerId, array $metadata = []): array
    {
        try {
            DB::beginTransaction();

            // 1. Validate reader exists and is active
            $reader = $this->validateReader($readerId);
            if (!$reader) {
                return $this->failResponse('Reader not found or inactive', null);
            }

            // 2. Check for duplicate scan (cooldown logic)
            if ($this->isDuplicateScan($tagUid, $readerId)) {
                return $this->duplicateResponse($tagUid, $readerId);
            }

            // 3. Find RFID tag and associated item
            $tag = RfidTag::where('tag_code', $tagUid)
                ->where('is_active', true)
                ->where('status', 'active')
                ->first();

            if (!$tag) {
                return $this->logAndFail('Tag not found or inactive', $tagUid, $readerId, null);
            }

            // 4. Get associated item
            $assignment = $tag->assignment;
            if (!$assignment || !$assignment->item) {
                return $this->logAndFail('Tag not assigned to any item', $tagUid, $readerId, null);
            }

            $item = $assignment->item;

            // 5. Detect action type
            $actionResolver = new RfidActionResolver();
            $action = $actionResolver->detectAction($item, $reader, $metadata);

            // 6. Create scan log entry
            $scanLog = $this->createScanLog($tagUid, $item, $reader, $action, $metadata);

            // 7. Sync with inventory system (Checkin/Checkout/Transfer)
            $syncService = new RfidSyncService();
            $syncResult = $syncService->syncWithInventorySystem($item, $reader, $action, $scanLog);

            // 8. Update item location tracking
            $this->updateItemLocation($item, $reader);

            // 9. Mark scan as processed
            $scanLog->update([
                'status' => 'processed',
                'related_model' => $syncResult['model'] ?? null,
                'related_id' => $syncResult['id'] ?? null,
            ]);

            DB::commit();

            return [
                'status' => 'success',
                'message' => "Item '{$item->name}' {$action} in {$reader->warehouse->name}",
                'item_id' => $item->id,
                'action' => $action,
                'scan_log_id' => $scanLog->id,
                'warehouse_id' => $reader->warehouse_id,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('RFID Scan Error: ' . $e->getMessage(), [
                'tag_uid' => $tagUid,
                'reader_id' => $readerId,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->failResponse($e->getMessage(), null);
        }
    }

    /**
     * Validate that reader exists and is active
     */
    private function validateReader(int $readerId): ?RfidReader
    {
        return RfidReader::where('id', $readerId)
            ->where('status', 'active')
            ->with('warehouse')
            ->first();
    }

    /**
     * Check if this is a duplicate scan (within cooldown period)
     */
    private function isDuplicateScan(string $tagUid, int $readerId): bool
    {
        $lastScan = RfidScanLog::where('tag_code', $tagUid)
            ->where('reader_id', $readerId)
            ->where('status', 'processed')
            ->latest('created_at')
            ->first();

        if (!$lastScan) {
            return false;
        }

        // Check if within cooldown period
        $secondsElapsed = now()->diffInSeconds($lastScan->created_at);
        return $secondsElapsed < self::DUPLICATE_COOLDOWN;
    }

    /**
     * Create RFID scan log entry
     */
    private function createScanLog(
        string $tagUid,
        Item $item,
        RfidReader $reader,
        string $action,
        array $metadata = []
    ): RfidScanLog {
        return RfidScanLog::create([
            'tag_code' => $tagUid,
            'item_id' => $item->id,
            'reader_id' => $reader->id,
            'warehouse_id' => $reader->warehouse_id,
            'location' => $reader->location,
            'action' => $action,
            'status' => 'pending',
            'scanned_at' => now(),
            'account_id' => auth()->user()->account_id ?? null,
        ]);
    }

    /**
     * Update item location tracking
     */
    private function updateItemLocation(Item $item, RfidReader $reader): void
    {
        ItemLocation::updateOrCreate(
            [
                'item_id' => $item->id,
                'warehouse_id' => $reader->warehouse_id,
            ],
            [
                'zone' => $reader->location,
                'last_seen_at' => now(),
                'last_reader_id' => $reader->id,
                'account_id' => auth()->user()->account_id ?? null,
            ]
        );
    }

    /**
     * Log failure and create failed scan entry
     */
    private function logAndFail(string $message, string $tagUid, int $readerId, ?int $itemId): array
    {
        $reader = RfidReader::find($readerId);

        RfidScanLog::create([
            'tag_code' => $tagUid,
            'item_id' => $itemId,
            'reader_id' => $readerId,
            'warehouse_id' => $reader?->warehouse_id,
            'location' => $reader?->location,
            'status' => 'failed',
            'scanned_at' => now(),
            'account_id' => auth()->user()->account_id ?? null,
        ]);

        Log::warning("RFID Scan Failed: {$message}", [
            'tag_uid' => $tagUid,
            'reader_id' => $readerId,
        ]);

        return $this->failResponse($message, null);
    }

    /**
     * Response helpers
     */
    private function failResponse(string $message, ?string $data = null): array
    {
        return [
            'status' => 'error',
            'message' => $message,
            'data' => $data,
        ];
    }

    private function duplicateResponse(string $tagUid, int $readerId): array
    {
        $scanLog = RfidScanLog::create([
            'tag_code' => $tagUid,
            'reader_id' => $readerId,
            'status' => 'duplicate',
            'scanned_at' => now(),
            'account_id' => auth()->user()->account_id ?? null,
        ]);

        return [
            'status' => 'duplicate',
            'message' => 'Duplicate scan detected (cooldown)',
            'scan_log_id' => $scanLog->id,
        ];
    }

    /**
     * Get recent scan logs
     */
    public function getRecentScans(int $warehouseId = null, int $minutes = 60): array
    {
        $query = RfidScanLog::with(['item', 'reader', 'warehouse'])
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->where('status', '!=', 'duplicate');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->latest('created_at')->get()->toArray();
    }

    /**
     * Get scan summary for dashboard
     */
    public function getScanSummary(int $warehouseId = null, int $minutes = 60): array
    {
        $query = RfidScanLog::where('created_at', '>=', now()->subMinutes($minutes));

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return [
            'total_scans' => $query->count(),
            'by_action' => $query->groupBy('action')->selectRaw('action, count(*) as count')->pluck('count', 'action')->toArray(),
            'by_status' => $query->groupBy('status')->selectRaw('status, count(*) as count')->pluck('count', 'status')->toArray(),
            'unique_items' => $query->distinct('item_id')->count(),
            'success_rate' => $this->calculateSuccessRate($query),
        ];
    }

    private function calculateSuccessRate($query): float
    {
        $total = $query->count();
        if ($total === 0) return 0;

        $processed = (clone $query)->where('status', 'processed')->count();
        return round(($processed / $total) * 100, 2);
    }
}
