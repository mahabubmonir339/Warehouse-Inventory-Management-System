<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidTagAssignment extends Model
{
    protected $fillable = [
        'rfid_tag_id',
        'assignable_id',
        'assignable_type',
        'assigned_at',
        'unassigned_at'
    ];

    public function tag()
    {
        return $this->belongsTo(RfidTag::class, 'rfid_tag_id');
    }

    public function assignable()
    {
        return $this->morphTo();
    }
}