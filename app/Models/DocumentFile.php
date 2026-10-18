<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentFile extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['size' => 'integer']; }
    public function version() { return $this->belongsTo(DocumentVersion::class, 'document_version_id'); }
    public function getSizeLabelAttribute(): string { return number_format($this->size / 1024 / 1024, 2, ',', '.').' MB'; }
}
