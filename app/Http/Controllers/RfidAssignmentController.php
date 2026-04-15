<?php

namespace App\Http\Controllers;

use App\Models\RfidTagAssignment;
use Illuminate\Http\Request;

class RfidAssignmentController extends Controller
{
    public function assign(Request $request)
    {
        $data = RfidTagAssignment::create([
            'rfid_tag_id' => $request->rfid_tag_id,
            'assignable_id' => $request->assignable_id,
            'assignable_type' => $request->assignable_type,
            'assigned_at' => now()
        ]);

        return response()->json($data);
    }
}
