<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidTag extends Model
{
    //
    protected $fillable = [
        'tag_uid',
        'type',
        'status'
    ];

    public function assignment()
    {
        return $this->hasOne(RfidTagAssignment::class);
    }

    public function scans()
    {
        return $this->hasMany(RfidScan::class);
    }
}
