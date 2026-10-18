<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InboxMessage extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['read_at' => 'datetime']; }
    public function document() { return $this->belongsTo(Document::class); }
}
