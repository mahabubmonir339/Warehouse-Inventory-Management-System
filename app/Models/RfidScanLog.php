<?php

namespace App\Models;

class RfidScanLog extends Model
{
    protected $fillable = [
        'tag_code',
        'item_id',
        'reader_id',
        'warehouse_id',
        'location',
        'action',
        'related_model',
        'related_id',
        'status',
        'scanned_at',
        'account_id',
    ];

    // protected $casts = [
    //     'scanned_at' => 'datetime',
    //     'created_at' => 'datetime',
    //     'updated_at' => 'datetime',
    // ];

    public $casts = [
        'scanned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function reader()
    {
        return $this->belongsTo(RfidReader::class, 'reader_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function tag()
    {
        return $this->belongsTo(RfidTag::class, 'tag_code', 'tag_code');
    }

    public function relatedModel()
    {
        if (!$this->related_model || !$this->related_id) {
            return null;
        }

        $modelClass = "App\\Models\\" . $this->related_model;
        if (class_exists($modelClass)) {
            return $modelClass::find($this->related_id);
        }

        return null;
    }

    public function scopeOfWarehouse($query, $warehouse)
    {
        return $query->where('warehouse_id', $warehouse);
    }

    public function scopeOfAccount($query, $account = null)
    {
        return $query->where('account_id', $account ?? auth()->user()->account_id ?? null);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
}
