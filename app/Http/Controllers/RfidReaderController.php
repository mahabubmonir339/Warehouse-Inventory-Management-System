<?php

namespace App\Http\Controllers;

use App\Models\RfidReader;
use Illuminate\Http\Request;

class RfidReaderController extends Controller
{
    public function store(Request $request)
    {
        return RfidReader::create($request->all());
    }
}
