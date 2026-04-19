<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidScanLog extends Model
{
    protected $fillable = ['tag_code', 'item_id', 'location', 'scanned_at'];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
