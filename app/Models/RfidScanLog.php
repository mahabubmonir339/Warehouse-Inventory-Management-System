<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidScanLog extends Model
{
    protected $fillable = ['tag_code', 'item_id', 'location', 'scanned_at'];
}
