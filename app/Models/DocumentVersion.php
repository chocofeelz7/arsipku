<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVersion extends Model
{
    public const LABELS = ['draft' => 'Draf', 'submitted' => 'Menunggu Validasi', 'revision' => 'Perlu Revisi', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['number' => 'integer', 'document_date' => 'date', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function document() { return $this->belongsTo(Document::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function files() { return $this->hasMany(DocumentFile::class); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function getStatusLabelAttribute(): string { return self::LABELS[$this->status] ?? $this->status; }
    public function getEditableAttribute(): bool { return in_array($this->status, ['draft', 'revision'], true); }
}
