<?php

namespace App\Services;

use App\Models\{Activity, Document, DocumentFile, DocumentVersion, InboxMessage, User};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ArchiveWorkflow
{
    public function assertOwner(User $actor, Document $document): void
    {
        abort_unless($actor->is_active && ! $actor->is_admin && $actor->division_id === $document->division_id, 403);
    }

    public function save(User $actor, array $metadata, ?Document $document, ?UploadedFile $main, array $attachments, array $removeIds = [], ?int $expectedVersionId = null): Document
    {
        abort_unless($actor->is_active && ! $actor->is_admin && $actor->division_id, 403);
        $newPaths = [];
        $unusedPaths = [];
        try {
            $result = DB::transaction(function () use ($actor, $metadata, $document, $main, $attachments, $removeIds, $expectedVersionId, &$newPaths, &$unusedPaths) {
                if ($document) {
                    $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
                    $this->assertOwner($actor, $document);
                    $version = $document->versions()->orderByDesc('number')->firstOrFail();
                    abort_if($expectedVersionId !== null && $version->id !== $expectedVersionId, 409, 'Versi sudah berubah. Muat ulang formulir.');
                    abort_unless($version->editable, 409, 'Pengajuan terkunci. Buat revisi baru setelah keputusan.');
                    $before = $version->only(array_keys($metadata));
                    $version->update($metadata);
                } else {
                    $document = Document::create(['code' => 'AKU-'.Str::ulid(), 'division_id' => $actor->division_id, 'created_by' => $actor->id]);
                    $version = $document->versions()->create([...$metadata, 'number' => 1, 'created_by' => $actor->id]);
                    $before = null;
                }
                if ($removeIds) {
                    $removable = $version->files()->where('kind', 'attachment')->whereIn('id', $removeIds)->get();
                    if ($removable->count() !== count(array_unique($removeIds))) {
                        throw ValidationException::withMessages(['attachments' => 'Pilihan lampiran tidak sah.']);
                    }
                    foreach ($removable as $file) {
                        $unusedPaths[] = $file->path;
                        $file->delete();
                    }
                }
                if ($main) {
                    foreach ($version->files()->where('kind', 'main')->get() as $file) {
                        $unusedPaths[] = $file->path;
                        $file->delete();
                    }
                    $this->storeFile($version, $actor, $main, 'main', $newPaths);
                }
                foreach ($attachments as $file) {
                    $this->storeFile($version, $actor, $file, 'attachment', $newPaths);
                }
                $this->validatePackage($version, false);
                Activity::record($actor, $before === null ? 'Dokumen dibuat' : 'Draf diperbarui', $document, ['version' => $version->number, 'before' => $before, 'after' => $metadata]);
                $document->touch();
                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk('archive')->delete($newPaths);
            throw $exception;
        }
        // A copied version may still reference the original file.
        foreach (array_unique($unusedPaths) as $path) {
            if (! DocumentFile::where('path', $path)->exists()) {
                Storage::disk('archive')->delete($path);
            }
        }
        return $result;
    }

    private function storeFile(DocumentVersion $version, User $actor, UploadedFile $upload, string $kind, array &$newPaths): void
    {
        $path = $upload->store('documents/'.$version->document_id, 'archive');
        if (! $path) {
            throw ValidationException::withMessages(['main_file' => 'Berkas gagal disimpan. Coba kembali atau hubungi administrator.']);
        }
        $newPaths[] = $path;
        $name = mb_substr(str_replace(["\r", "\n", '/', '\\'], '_', $upload->getClientOriginalName()), 0, 240);
        $version->files()->create(['kind' => $kind, 'original_name' => $name, 'path' => $path, 'mime' => $upload->getMimeType() ?? 'application/octet-stream', 'size' => $upload->getSize(), 'sha256' => hash_file('sha256', $upload->getRealPath()), 'uploaded_by' => $actor->id]);
    }

    private function validatePackage(DocumentVersion $version, bool $requireMain): void
    {
        $files = $version->files()->get();
        if ($requireMain && $files->where('kind', 'main')->count() !== 1) {
            throw ValidationException::withMessages(['main_file' => 'Satu berkas utama wajib tersedia sebelum diajukan.']);
        }
        if ($files->where('kind', 'attachment')->count() > config('archive.max_attachments') || $files->sum('size') > config('archive.max_package_kb') * 1024) {
            throw ValidationException::withMessages(['attachments' => 'Jumlah lampiran atau ukuran total paket melewati batas aplikasi.']);
        }
        if ($requireMain) {
            foreach ($files as $file) {
                if (! Storage::disk('archive')->exists($file->path) || Storage::disk('archive')->size($file->path) !== $file->size || hash_file('sha256', Storage::disk('archive')->path($file->path)) !== $file->sha256) {
                    throw ValidationException::withMessages(['main_file' => 'Salah satu berkas hilang atau berubah. Hubungi administrator sebelum melanjutkan.']);
                }
            }
        }
    }

    public function submit(User $actor, Document $document): void
    {
        DB::transaction(function () use ($actor, $document) {
            $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $this->assertOwner($actor, $document);
            $version = $document->versions()->orderByDesc('number')->firstOrFail();
            abort_unless($version->editable, 409, 'Pengajuan sudah dikunci atau diputuskan.');
            if ($document->active_version_id && ! $version->revision_note) {
                throw ValidationException::withMessages(['revision_note' => 'Catatan revisi wajib diisi.']);
            }
            $this->validatePackage($version, true);
            $version->update(['status' => 'submitted', 'submitted_at' => now(), 'submission_token' => (string) Str::uuid(), 'reviewed_at' => null, 'reviewed_by' => null]);
            foreach (User::where('is_active', true)->where('is_validator', true)->where('is_admin', true)->get() as $validator) {
                InboxMessage::create(['user_id' => $validator->id, 'document_id' => $document->id, 'message' => 'Pengajuan baru: '.$version->title]);
            }
            Activity::record($actor, 'Diajukan untuk validasi', $document, ['version' => $version->number]);
        });
    }

    public function withdraw(User $actor, Document $document): void
    {
        DB::transaction(function () use ($actor, $document) {
            $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $this->assertOwner($actor, $document);
            $version = $document->versions()->orderByDesc('number')->firstOrFail();
            abort_unless($version->status === 'submitted', 409, 'Pengajuan tidak dapat ditarik.');
            $version->update(['status' => 'draft', 'submitted_at' => null]);
            Activity::record($actor, 'Pengajuan ditarik', $document, ['version' => $version->number]);
        });
    }

    public function review(User $actor, DocumentVersion $target, string $decision, ?string $note, ?string $expectedToken = null): void
    {
        abort_unless($actor->is_active && $actor->is_admin && $actor->is_validator, 403);
        abort_unless(in_array($decision, ['approved', 'revision', 'rejected'], true), 422);
        if ($decision !== 'approved' && ! trim($note ?? '')) {
            throw ValidationException::withMessages(['note' => 'Catatan wajib untuk revisi atau penolakan.']);
        }
        DB::transaction(function () use ($actor, $target, $decision, $note, $expectedToken) {
            $document = Document::whereKey($target->document_id)->lockForUpdate()->firstOrFail();
            $version = $document->versions()->whereKey($target->id)->firstOrFail();
            abort_if($expectedToken !== null && ! hash_equals($version->submission_token ?? '', $expectedToken), 409, 'Pengajuan berubah sejak halaman dibuka. Periksa ulang seluruh berkas.');
            abort_unless($version->status === 'submitted' && (int) $document->versions()->max('number') === $version->number, 409, 'Pengajuan sudah berubah atau telah diputuskan. Muat ulang halaman.');
            abort_if($version->created_by === $actor->id, 403, 'Tidak dapat memvalidasi pengajuan sendiri.');
            if ($decision === 'approved') {
                $this->validatePackage($version, true);
            }
            $version->update(['status' => $decision, 'reviewed_at' => now(), 'reviewed_by' => $actor->id]);
            $version->reviews()->create(['user_id' => $actor->id, 'decision' => $decision, 'note' => $note]);
            if ($decision === 'approved') {
                $document->update(['active_version_id' => $version->id]);
            }
            foreach (User::where('division_id', $document->division_id)->where('is_active', true)->get() as $owner) {
                InboxMessage::create(['user_id' => $owner->id, 'document_id' => $document->id, 'message' => $version->status_label.': '.$version->title]);
            }
            Activity::record($actor, 'Validasi: '.$version->status_label, $document, ['version' => $version->number, 'note' => $note]);
        });
    }

    public function revise(User $actor, Document $document): void
    {
        DB::transaction(function () use ($actor, $document) {
            $document = Document::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $this->assertOwner($actor, $document);
            $latest = $document->versions()->orderByDesc('number')->firstOrFail();
            abort_unless(in_array($latest->status, ['approved', 'rejected'], true), 409, 'Selesaikan versi yang sedang berjalan terlebih dahulu.');
            $version = $latest->replicate(['number', 'status', 'created_by', 'submitted_at', 'submission_token', 'reviewed_at', 'reviewed_by', 'revision_note']);
            $version->fill(['number' => $latest->number + 1, 'status' => 'draft', 'created_by' => $actor->id]);
            $version->save();
            foreach ($latest->files as $file) {
                $copy = $file->replicate(['document_version_id']);
                $copy->document_version_id = $version->id;
                $copy->save();
            }
            Activity::record($actor, 'Versi baru dibuat', $document, ['from' => $latest->number, 'version' => $version->number]);
        });
    }
}
