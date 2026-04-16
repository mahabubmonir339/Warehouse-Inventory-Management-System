<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RfidTag extends Model
{
    protected $fillable = ['tag_code', 'is_active'];

    public function assignment()
    {
        return $this->hasOne(RfidTagAssignment::class);
    }
}
