<?php

namespace App\Http\Controllers;

use App\Actions\Tec\PrepareOrder;
use App\Models\Checkin;
use App\Models\Item;
use App\Models\RfidTag;
use App\Models\RfidTagAssignment;
use App\Models\RfidScanLog;
use Illuminate\Http\Request;

class RfidController extends Controller
{
    //  Live Scan Page
    public function live()
    {
        return inertia('RFID/Live');
    }

    //  Tag Assign Page
    public function assign()
    {
        $tags = RfidTag::all();
        $items = Item::all();

        return inertia('RFID/Assign', compact('tags', 'items'));
    }

    //  Save Tag Assign
    public function storeAssign(Request $request)
    {
        $request->validate([
            'rfid_tag_id' => 'required|exists:rfid_tags,id',
            'item_id' => 'required|exists:items,id',
        ]);

        RfidTagAssignment::where('rfid_tag_id', $request->rfid_tag_id)->delete();

        RfidTagAssignment::create([
            'rfid_tag_id' => $request->rfid_tag_id,
            'item_id' => $request->item_id,
        ]);

        return back()->with('success', 'Tag Assigned Successfully');
    }

    // Logs Page
    public function logs()
    {
        $logs = RfidScanLog::with('item')->latest('created_at')->get();

        return inertia('RFID/Logs', compact('logs'));
    }

    //  Scan API (IMPORTANT)

    public function scan(Request $request)
    {
        $request->validate([
            'tag_code' => 'required|string',
            'location' => 'nullable|string',
            'warehouse_id' => 'nullable|integer',
        ]);

        $tag = RfidTag::where('tag_code', $request->tag_code)->first();

        if (! $tag) {
            return response()->json(['error' => 'Tag not found'], 404);
        }

        $assignment = $tag->assignment;
        $item_id = $assignment ? $assignment->item_id : null;

        // DUPLICATE PROTECTION (very important) — check before creating a new log
        $lastLog = RfidScanLog::where('tag_code', $tag->tag_code)
            ->latest('created_at')
            ->first();

        if ($lastLog && now()->diffInSeconds($lastLog->created_at) < 3) {
            return response()->json(['status' => 'duplicate ignored']);
        }

        RfidScanLog::create([
            'tag_code' => $tag->tag_code,
            'item_id' => $item_id,
            'location' => $request->location,
        ]);

        if (! $item_id) {
            return response()->json(['status' => 'no item assigned']);
        }

        $data = [
            'warehouse_id' => $request->warehouse_id ?? 1,
            'contact_id'   => null,
            'items' => [
                [
                    'item_id' => $item_id,
                    'quantity' => 1,
                    'unit_id' => null,
                ],
            ],
        ];

        $checkin = (new PrepareOrder($data, null, new Checkin()))
            ->process()
            ->save();

        event(new \App\Events\CheckinEvent($checkin, 'created'));

        return response()->json([
            'status' => 'checkin created',
            'checkin_id' => $checkin->id,
            'item_id' => $item_id,
        ]);
    }
}
