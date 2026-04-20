<?php

namespace App\Models;

class ItemLocation extends Model
{
    protected $fillable = [
        'item_id',
        'warehouse_id',
        'zone',
        'last_seen_at',
        'last_reader_id',
        'account_id',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lastReader()
    {
        return $this->belongsTo(RfidReader::class, 'last_reader_id');
    }

    public function scopeOfItem($query, $item)
    {
        return $query->where('item_id', $item);
    }

    public function scopeOfWarehouse($query, $warehouse)
    {
        return $query->where('warehouse_id', $warehouse);
    }

    public function scopeOfAccount($query)
    {
        return $query->where('account_id', auth()->user()->account_id);
    }

    public function scopeRecentlyUpdated($query, $minutes = 60)
    {
        return $query->where('last_seen_at', '>=', now()->subMinutes($minutes));
    }
}
