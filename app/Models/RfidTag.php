<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RfidTag extends Model
{
    protected $fillable = [
        'tag_code',
        'tag_type',
        'is_active',
        'status',
        'account_id',
    ];

    public $casts = [
        'is_active' => 'boolean',
        'status' => 'string',
    ];

    public function assignment()
    {
        return $this->hasOne(RfidTagAssignment::class, 'rfid_tag_id');
    }

    public function item()
    {
        return $this->hasOneThrough(
            Item::class,
            RfidTagAssignment::class,
            'rfid_tag_id',
            'id',
            'id',
            'item_id'
        );
    }

    public function scans()
    {
        return $this->hasMany(RfidScanLog::class, 'tag_code', 'tag_code');
    }

    public function scopeActive($query, $account = null)
    {
        return $query->where('is_active', true)->where('status', 'active')->where('account_id', $account ?? auth()->user()->account_id ?? null);
    }

    public function scopeOfAccount($query, $account = null)
    {
        return $query->where('account_id', $account ?? auth()->user()->account_id ?? null);
    }

    public function scopeUnassigned($query, $account = null)
    {
        return $query->whereDoesntHave('assignment')->where('account_id', $account ?? auth()->user()->account_id ?? null);
    }
}
