<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RfidTag;
use App\Models\RfidScan;
use App\Models\InventoryMovement;

class RfidScanController extends Controller
{
    public function scan(Request $request)
    {
        // STEP 4: Tag find
        $tag = RfidTag::where('tag_uid', $request->tag_uid)->first();

        if (!$tag) {
            return response()->json(['message' => 'Tag not found'], 404);
        }

        // Assignment → Item
        $assignment = $tag->assignment;

        if (!$assignment) {
            return response()->json(['message' => 'Tag not assigned'], 404);
        }

        $item = $assignment->assignable;

        // STEP 5: Save Scan
        RfidScan::create([
            'rfid_tag_id' => $tag->id,
            'rfid_reader_id' => $request->reader_id,
            'scanned_at' => now()
        ]);

        // STEP 6: Movement Track
        InventoryMovement::create([
            'item_id' => $item->id,
            'to_location_id' => $request->location_id,
            'rfid_tag_id' => $tag->id,
            'moved_at' => now()
        ]);

        return response()->json([
            'item_name' => $item->name,
            'item_id' => $item->id,
            'message' => 'Scan successful'
        ]);
    }
}
