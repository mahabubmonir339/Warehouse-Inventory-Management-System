<?php

namespace App\Services\Rfid;

use App\Models\Item;
use App\Models\RfidReader;
use App\Models\ItemLocation;

/**
 * RFID Action Resolver
 * Detects the type of action (IN, OUT, MOVE, TRANSFER) based on item state and reader location
 */
class RfidActionResolver
{
    /**
     * Detect the action type for a scanned item
     *
     * IN = Item entering warehouse for first time
     * OUT = Item leaving warehouse (checkout gate)
     * MOVE = Item moved within same warehouse
     * TRANSFER = Item moved to different warehouse
     *
     * @param Item $item
     * @param RfidReader $reader
     * @param array $metadata Optional metadata from scan
     * @return string Action type
     */
    public function detectAction(Item $item, RfidReader $reader, array $metadata = []): string
    {
        // Get current item location
        $currentLocation = $item->locationInWarehouse($reader->warehouse_id);

        // New item to this warehouse - it's an IN
        if (!$currentLocation) {
            return $this->isExitPoint($reader) ? 'OUT' : 'IN';
        }

        // Check if exit point (checkout/exit gate)
        if ($this->isExitPoint($reader)) {
            return 'OUT';
        }

        // Item being moved within same warehouse
        if ($currentLocation->zone !== $reader->location) {
            return 'MOVE';
        }

        // Get stock for this item in current and target warehouse
        $stock = $item->allStock()
            ->where('warehouse_id', $reader->warehouse_id)
            ->first();

        // If stock exists, it's a move/recheck
        return $currentLocation->zone === $reader->location ? 'MOVE' : 'TRANSFER';
    }

    /**
     * Determine if a reader is at an exit/checkout point
     *
     * Exit points are typically readers at gates or checkout locations
     */
    private function isExitPoint(RfidReader $reader): bool
    {
        $exitKeywords = ['exit', 'checkout', 'gate', 'shipping', 'out', 'dispatch'];

        $location = strtolower($reader->location);
        foreach ($exitKeywords as $keyword) {
            if (strpos($location, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if action is a warehouse transfer
     */
    private function isTransfer(Item $item, RfidReader $reader): bool
    {
        $lastKnownLocation = $item->currentLocation()->first();

        if (!$lastKnownLocation) {
            return false;
        }

        return $lastKnownLocation->warehouse_id !== $reader->warehouse_id;
    }

    /**
     * Validate if item is eligible for the detected action
     */
    public function validateAction(Item $item, string $action): bool
    {
        // Check item status
        if (!$item->exists) {
            return false;
        }

        // Add more validation rules as needed
        return true;
    }
}
