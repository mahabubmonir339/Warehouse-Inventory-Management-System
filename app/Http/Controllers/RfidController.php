<?php

namespace App\Http\Controllers;

use App\Models\RfidTag;
use App\Models\RfidTagAssignment;
use App\Models\RfidScanLog;
use App\Models\Item;
use Illuminate\Http\Request;

class RfidController extends Controller
{
    // 📡 Live Scan Page
    public function live()
    {
        return inertia('RFID/Live');
    }

    // 🏷️ Tag Assign Page
    public function assign()
    {
        $tags = RfidTag::all();
        $items = Item::all();

        return inertia('RFID/Assign', compact('tags', 'items'));
    }

    // 🏷️ Save Tag Assign
    public function storeAssign(Request $request)
    {
        // আগে check করো tag already assign কিনা
        RfidTagAssignment::where('rfid_tag_id', $request->rfid_tag_id)->delete();

        // নতুন assign
        RfidTagAssignment::create([
            'rfid_tag_id' => $request->rfid_tag_id,
            'item_id' => $request->item_id,
        ]);

        return back()->with('success', 'Tag Assigned Successfully');
    }

    // 📊 Logs Page
    public function logs()
    {
        $logs = RfidScanLog::latest()->get();

        return inertia('RFID/Logs', compact('logs'));
    }

    // 📡 Scan API (IMPORTANT)
    public function scan(Request $request)
    {
        $tag = RfidTag::where('tag_code', $request->tag_code)->first();

        if (!$tag) {
            return response()->json(['error' => 'Tag not found'], 404);
        }

        $assignment = $tag->assignment;

        $item_id = $assignment ? $assignment->item_id : null;

        // log save
        RfidScanLog::create([
            'tag_code' => $tag->tag_code,
            'item_id' => $item_id,
            'location' => $request->location,
        ]);

        return response()->json([
            'status' => 'ok',
            'item_id' => $item_id,
        ]);
    }
}
