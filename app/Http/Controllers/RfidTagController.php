<?php
namespace App\Http\Controllers;

use App\Models\RfidTag;
use Illuminate\Http\Request;

class RfidTagController extends Controller
{
    public function index()
    {
        return RfidTag::all();
    }

    public function store(Request $request)
    {
        $tag = RfidTag::create([
            'tag_uid' => $request->tag_uid,
            'type' => $request->type,
            'status' => 'active'
        ]);

        return response()->json($tag);
    }
}
