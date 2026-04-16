<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidTagAssignment extends Model
{
    protected $fillable = ['rfid_tag_id', 'item_id'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function tag()
    {
        return $this->belongsTo(RfidTag::class, 'rfid_tag_id');
    }
}
