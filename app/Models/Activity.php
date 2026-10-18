<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected static function booted(): void
    {
        static::creating(function (Activity $activity) { $activity->created_at ??= now(); });
    }
    protected function casts(): array { return ['details' => 'array', 'created_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function document() { return $this->belongsTo(Document::class); }

    public static function record(User $actor, string $action, ?Document $document = null, array $details = []): void
    {
        self::create(['user_id' => $actor->id, 'document_id' => $document?->id, 'action' => $action, 'details' => $details]);
    }
}
