<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidScan extends Model
{
    protected $fillable = [
        'rfid_tag_id',
        'rfid_reader_id',
        'scanned_at',
        'signal_strength'
    ];

    public function tag()
    {
        return $this->belongsTo(RfidTag::class, 'rfid_tag_id');
    }

    public function reader()
    {
        return $this->belongsTo(RfidReader::class, 'rfid_reader_id');
    }
}
