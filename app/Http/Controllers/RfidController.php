<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RfidTag;
use App\Models\RfidScan;
use App\Models\InventoryMovement;
use App\Models\RfidReader;

class RfidController extends Controller
{
    // 🔥 MAIN SCAN FUNCTION
    public function scan(Request $request)
    {
        // 1. Validate input
        $request->validate([
            'tag_uid' => 'required|string',
            'reader_code' => 'nullable|string'
        ]);

        // 2. Find RFID Tag
        $tag = RfidTag::where('tag_uid', $request->tag_uid)->first();

        if (!$tag) {
            return response()->json([
                'status' => false,
                'message' => 'RFID Tag not found'
            ], 404);
        }

        // 3. Get Assignment (Item/Location)
        $assignment = $tag->assignment;

        if (!$assignment) {
            return response()->json([
                'status' => false,
                'message' => 'Tag not assigned'
            ], 404);
        }

        $item = $assignment->assignable;

        // 4. Find Reader (optional)
        $reader = null;
        if ($request->reader_code) {
            $reader = RfidReader::where('device_code', $request->reader_code)->first();
        }

        // =========================
        // 🔹 STEP 5 → SAVE SCAN
        // =========================
        RfidScan::create([
            'rfid_tag_id'    => $tag->id,
            'rfid_reader_id' => $reader?->id,
            'scanned_at'     => now()
        ]);

        // =========================
        // 🔹 STEP 6 → SAVE MOVEMENT
        // =========================
        InventoryMovement::create([
            'item_id'        => $item->id,
            'to_location_id' => $reader->location_id ?? null,
            'rfid_tag_id'    => $tag->id,
            'moved_at'       => now()
        ]);

        // =========================
        // 🔹 RESPONSE
        // =========================
        return response()->json([
            'status' => true,
            'message' => 'Scan successful',
            'data' => [
                'item_id'   => $item->id,
                'item_name' => $item->name ?? 'N/A',
                'tag_uid'   => $tag->tag_uid,
                'location_id' => $reader->location_id ?? null
            ]
        ]);
    }
}
