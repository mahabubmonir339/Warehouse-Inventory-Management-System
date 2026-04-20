<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class RfidReader extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'ip_address',
        'warehouse_id',
        'location',
        'status',
        'read_range',
        'frequency',
        'account_id',
    ];

    public $casts = [
        'status' => 'string',
        'read_range' => 'integer',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function scans()
    {
        return $this->hasMany(RfidScanLog::class, 'reader_id');
    }

    public function itemLocations()
    {
        return $this->hasMany(ItemLocation::class, 'last_reader_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfWarehouse($query, $warehouse)
    {
        return $query->where('warehouse_id', $warehouse);
    }

    public function scopeOfAccount($query, $account = null)
    {
        return $query->where('account_id', $account ?? auth()->user()->account_id ?? null);
    }
}
