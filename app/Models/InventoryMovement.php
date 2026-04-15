<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'item_id',
        'from_location_id',
        'to_location_id',
        'rfid_tag_id',
        'moved_at'
    ];
}
