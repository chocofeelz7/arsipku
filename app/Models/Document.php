<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $guarded = [];

    public function division() { return $this->belongsTo(Division::class); }
    public function versions() { return $this->hasMany(DocumentVersion::class); }
    public function latestVersion() { return $this->hasOne(DocumentVersion::class)->latestOfMany('number'); }
    public function activeVersion() { return $this->belongsTo(DocumentVersion::class, 'active_version_id'); }
    public function activities() { return $this->hasMany(Activity::class); }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! $user->is_active) {
            return $query->whereRaw('1 = 0');
        }
        return $user->is_admin ? $query : $query->where('division_id', $user->division_id ?? 0);
    }
}
