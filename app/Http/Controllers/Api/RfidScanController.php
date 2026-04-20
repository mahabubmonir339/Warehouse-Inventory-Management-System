<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RfidScanLog;
use App\Models\RfidTag;
use App\Services\Rfid\RfidScanProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API Controller for RFID Scan Processing
 * Handles incoming scan data from RFID readers
 */
class RfidScanController extends Controller
{
    private RfidScanProcessor $processor;

    public function __construct(RfidScanProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * POST /api/rfid/scan
     * Process a single RFID scan
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processScan(Request $request): JsonResponse
    {
        $request->validate([
            'tag_uid' => 'required|string',
            'reader_id' => 'required|integer|exists:rfid_readers,id',
            'timestamp' => 'sometimes|timestamp',
            'signal_strength' => 'sometimes|numeric',
        ]);

        $result = $this->processor->processScan(
            $request->input('tag_uid'),
            $request->input('reader_id'),
            $request->all()
        );

        $statusCode = $result['status'] === 'success' ? 200 : ($result['status'] === 'duplicate' ? 409 : 422);

        return response()->json($result, $statusCode);
    }

    /**
     * POST /api/rfid/bulk-scan
     * Process multiple scans in batch
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkScan(Request $request): JsonResponse
    {
        $request->validate([
            'scans' => 'required|array|min:1',
            'scans.*.tag_uid' => 'required|string',
            'scans.*.reader_id' => 'required|integer|exists:rfid_readers,id',
        ]);

        $results = [];
        foreach ($request->input('scans') as $scan) {
            $results[] = $this->processor->processScan(
                $scan['tag_uid'],
                $scan['reader_id'],
                $scan
            );
        }

        return response()->json([
            'status' => 'completed',
            'total' => count($results),
            'successful' => count(array_filter($results, fn($r) => $r['status'] === 'success')),
            'scans' => $results,
        ]);
    }

    /**
     * GET /api/rfid/recent-scans
     * Get recent RFID scans
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRecentScans(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id' => 'sometimes|integer|exists:warehouses,id',
            'minutes' => 'sometimes|integer|min:1|max:1440',
        ]);

        $scans = $this->processor->getRecentScans(
            $request->input('warehouse_id'),
            $request->input('minutes', 60)
        );

        return response()->json([
            'status' => 'success',
            'count' => count($scans),
            'data' => $scans,
        ]);
    }

    /**
     * GET /api/rfid/scan-summary
     * Get scan summary statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getScanSummary(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id' => 'sometimes|integer|exists:warehouses,id',
            'minutes' => 'sometimes|integer|min:1|max:1440',
        ]);

        $summary = $this->processor->getScanSummary(
            $request->input('warehouse_id'),
            $request->input('minutes', 60)
        );

        return response()->json([
            'status' => 'success',
            'data' => $summary,
        ]);
    }

    /**
     * GET /api/rfid/scan-logs
     * List scan logs with filtering
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listScans(Request $request): JsonResponse
    {
        $request->validate([
            'warehouse_id' => 'sometimes|integer',
            'action' => 'sometimes|in:IN,OUT,MOVE,TRANSFER',
            'status' => 'sometimes|in:pending,processed,failed,duplicate',
            'days' => 'sometimes|integer|min:1|max:90',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = RfidScanLog::with(['item', 'reader', 'warehouse']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->has('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('days')) {
            $query->where('created_at', '>=', now()->subDays($request->input('days')));
        }

        $logs = $query->latest('created_at')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'status' => 'success',
            'data' => $logs,
        ]);
    }

    /**
     * DELETE /api/rfid/scan-logs/{id}
     * Delete and rollback a scan
     *
     * @param RfidScanLog $scanLog
     * @return JsonResponse
     */
    public function deleteScan(RfidScanLog $scanLog): JsonResponse
    {
        $this->authorize('delete', $scanLog);

        $syncService = new \App\Services\Rfid\RfidSyncService();
        $rolled = $syncService->rollbackScan($scanLog);

        if ($rolled) {
            return response()->json([
                'status' => 'success',
                'message' => 'Scan rolled back successfully',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to rollback scan',
        ], 500);
    }
}
