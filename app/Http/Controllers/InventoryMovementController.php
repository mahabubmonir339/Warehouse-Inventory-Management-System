<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;

class InventoryMovementController extends Controller
{
    public function index()
    {
        return InventoryMovement::latest()->get();
    }
}
