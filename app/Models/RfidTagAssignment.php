<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RfidTagAssignment extends Model
{
    protected $fillable = [
        'rfid_tag_id',
        'item_id',
        'account_id',
        'assigned_by',
        'assigned_at',
        'status',
    ];

    public $casts = [
        'assigned_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function tag()
    {
        return $this->belongsTo(RfidTag::class, 'rfid_tag_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scans()
    {
        return $this->hasManyThrough(
            RfidScanLog::class,
            RfidTag::class,
            'id',
            'tag_code',
            'rfid_tag_id',
            'tag_code'
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOfAccount($query, $account = null)
    {
        return $query->where('account_id', $account ?? auth()->user()->account_id ?? null);
    }
}
