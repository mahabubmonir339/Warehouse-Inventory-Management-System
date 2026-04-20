<?php

namespace App\Services\Rfid;

use App\Models\Item;
use App\Models\RfidReader;
use App\Models\RfidScanLog;
use App\Models\Checkin;
use App\Models\CheckinItem;
use App\Models\Checkout;
use App\Models\CheckoutItem;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * RFID Sync Service
 * Synchronizes RFID scan events with the existing inventory system
 * (Checkin, Checkout, Transfer operations)
 */
class RfidSyncService
{
    /**
     * Sync RFID scan with inventory system
     * Creates appropriate inventory transaction based on action type
     *
     * @param Item $item
     * @param RfidReader $reader
     * @param string $action IN|OUT|MOVE|TRANSFER
     * @param RfidScanLog $scanLog
     * @return array Result with model name and ID
     */
    public function syncWithInventorySystem(
        Item $item,
        RfidReader $reader,
        string $action,
        RfidScanLog $scanLog
    ): array {
        try {
            $result = match ($action) {
                'IN' => $this->createCheckin($item, $reader, $scanLog),
                'OUT' => $this->createCheckout($item, $reader, $scanLog),
                'MOVE' => $this->logMovement($item, $reader, $scanLog),
                'TRANSFER' => $this->createTransfer($item, $reader, $scanLog),
                default => ['success' => false, 'message' => 'Unknown action'],
            };

            return $result;

        } catch (Exception $e) {
            Log::error("RFID Sync Error for action {$action}", [
                'item_id' => $item->id,
                'reader_id' => $reader->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'model' => null,
                'id' => null,
            ];
        }
    }

    /**
     * Create Checkin transaction for IN action
     */
    private function createCheckin(Item $item, RfidReader $reader, RfidScanLog $scanLog): array
    {
        try {
            $checkin = Checkin::create([
                'date' => now(),
                'reference' => 'RFID-' . uniqid(),
                'warehouse_id' => $reader->warehouse_id,
                'user_id' => auth()->id() ?? 1,
                'account_id' => auth()->user()->account_id ?? $item->account_id,
                'draft' => false,
                'details' => "Auto checkin via RFID tag at {$reader->location}",
                'extra_attributes' => [
                    'rfid_reader_id' => $reader->id,
                    'rfid_scan_log_id' => $scanLog->id,
                    'automated' => true,
                ],
            ]);

            // Create checkin item entry
            CheckinItem::create([
                'checkin_id' => $checkin->id,
                'item_id' => $item->id,
                'quantity' => 1,
                'details' => "RFID detected at {$reader->location}",
            ]);

            // Update stock
            $stock = $item->allStock()
                ->where('warehouse_id', $reader->warehouse_id)
                ->first();

            if ($stock) {
                $stock->increment('quantity', 1);
            } else {
                $item->allStock()->create([
                    'warehouse_id' => $reader->warehouse_id,
                    'quantity' => 1,
                    'account_id' => auth()->user()->account_id ?? $item->account_id,
                ]);
            }

            return [
                'success' => true,
                'model' => 'Checkin',
                'id' => $checkin->id,
                'message' => 'Checkin created automatically',
            ];

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Create Checkout transaction for OUT action
     */
    private function createCheckout(Item $item, RfidReader $reader, RfidScanLog $scanLog): array
    {
        try {
            $checkout = Checkout::create([
                'date' => now(),
                'reference' => 'RFID-' . uniqid(),
                'warehouse_id' => $reader->warehouse_id,
                'user_id' => auth()->id() ?? 1,
                'account_id' => auth()->user()->account_id ?? $item->account_id,
                'draft' => false,
                'details' => "Auto checkout via RFID tag at {$reader->location}",
                'extra_attributes' => [
                    'rfid_reader_id' => $reader->id,
                    'rfid_scan_log_id' => $scanLog->id,
                    'automated' => true,
                ],
            ]);

            // Create checkout item entry
            CheckoutItem::create([
                'checkout_id' => $checkout->id,
                'item_id' => $item->id,
                'quantity' => 1,
                'details' => "RFID detected at {$reader->location}",
            ]);

            // Update stock - decrease
            $stock = $item->allStock()
                ->where('warehouse_id', $reader->warehouse_id)
                ->first();

            if ($stock && $stock->quantity > 0) {
                $stock->decrement('quantity', 1);
            }

            return [
                'success' => true,
                'model' => 'Checkout',
                'id' => $checkout->id,
                'message' => 'Checkout created automatically',
            ];

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Log movement within same warehouse (MOVE action)
     * This doesn't create a transaction, just logs the movement
     */
    private function logMovement(Item $item, RfidReader $reader, RfidScanLog $scanLog): array
    {
        // Movement is recorded in item_locations and rfid_scan_logs
        // No inventory transaction needed
        return [
            'success' => true,
            'model' => 'RfidScanLog',
            'id' => $scanLog->id,
            'message' => 'Movement logged',
        ];
    }

    /**
     * Create Transfer transaction for TRANSFER action
     */
    private function createTransfer(Item $item, RfidReader $reader, RfidScanLog $scanLog): array
    {
        try {
            // Find last known warehouse
            $lastLocation = $item->currentLocation()->first();
            $fromWarehouseId = $lastLocation?->warehouse_id ?? $reader->warehouse_id;

            // Prevent self-transfer
            if ($fromWarehouseId === $reader->warehouse_id) {
                return [
                    'success' => true,
                    'model' => 'RfidScanLog',
                    'id' => $scanLog->id,
                    'message' => 'Same warehouse - no transfer needed',
                ];
            }

            $transfer = Transfer::create([
                'date' => now(),
                'reference' => 'RFID-' . uniqid(),
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $reader->warehouse_id,
                'user_id' => auth()->id() ?? 1,
                'account_id' => auth()->user()->account_id ?? $item->account_id,
                'draft' => false,
                'details' => "Auto transfer via RFID tag detected at {$reader->location}",
                'extra_attributes' => [
                    'rfid_reader_id' => $reader->id,
                    'rfid_scan_log_id' => $scanLog->id,
                    'automated' => true,
                ],
            ]);

            // Create transfer item entry
            TransferItem::create([
                'transfer_id' => $transfer->id,
                'item_id' => $item->id,
                'quantity' => 1,
                'details' => "RFID detected transfer from warehouse {$fromWarehouseId}",
            ]);

            // Update stock in both warehouses
            $fromStock = $item->allStock()
                ->where('warehouse_id', $fromWarehouseId)
                ->first();

            if ($fromStock && $fromStock->quantity > 0) {
                $fromStock->decrement('quantity', 1);
            }

            $toStock = $item->allStock()
                ->where('warehouse_id', $reader->warehouse_id)
                ->first();

            if ($toStock) {
                $toStock->increment('quantity', 1);
            } else {
                $item->allStock()->create([
                    'warehouse_id' => $reader->warehouse_id,
                    'quantity' => 1,
                    'account_id' => auth()->user()->account_id ?? $item->account_id,
                ]);
            }

            return [
                'success' => true,
                'model' => 'Transfer',
                'id' => $transfer->id,
                'message' => 'Transfer created automatically',
            ];

        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Rollback a scan and its related inventory transaction
     */
    public function rollbackScan(RfidScanLog $scanLog): bool
    {
        try {
            DB::beginTransaction();

            if ($scanLog->related_model && $scanLog->related_id) {
                $modelClass = "App\\Models\\" . $scanLog->related_model;
                if (class_exists($modelClass)) {
                    $model = $modelClass::find($scanLog->related_id);
                    if ($model) {
                        // Delete associated items and reverse stock
                        if (method_exists($model, 'items')) {
                            foreach ($model->items as $transactionItem) {
                                $transactionItem->delete();
                            }
                        }
                        $model->delete();
                    }
                }
            }

            $scanLog->update(['status' => 'cancelled']);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('RFID Rollback Error', [
                'scan_log_id' => $scanLog->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
