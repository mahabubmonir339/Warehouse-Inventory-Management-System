<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidReader extends Model
{
    protected $fillable = [
        'name',
        'device_code',
        'ip_address',
        'location_id',
        'status'
    ];

    public function scans()
    {
        return $this->hasMany(RfidScan::class);
    }
}
