<?php

namespace App\Http\Controllers;

use App\Models\{Activity, Category, Division, Document, DocumentFile, DocumentVersion};
use App\Services\ArchiveWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class DocumentController extends Controller
{
    private function query(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:150', 'status' => ['nullable', Rule::in(array_keys(DocumentVersion::LABELS))], 'category' => 'nullable|integer', 'division' => 'nullable|integer', 'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);
        $active = $request->boolean('active');
        $relation = $active ? 'activeVersion' : 'latestVersion';
        $query = Document::visibleTo($request->user())->with(['division', 'latestVersion.category', 'activeVersion.category']);
        if ($active) { $query->whereNotNull('active_version_id'); }
        if ($request->user()->is_admin && $request->filled('division')) { $query->where('division_id', $request->integer('division')); }
        $query->whereHas($relation, function ($version) use ($request) {
            if ($request->filled('q')) {
                $term = '%'.$request->string('q').'%';
                $version->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('document_number', 'like', $term)->orWhere('keywords', 'like', $term)->orWhereHas('document', fn ($d) => $d->where('code', 'like', $term)));
            }
            if ($request->filled('status')) { $version->where('status', $request->string('status')); }
            if ($request->filled('category')) { $version->where('category_id', $request->integer('category')); }
            if ($request->filled('from')) { $version->whereDate('document_date', '>=', $request->string('from')); }
            if ($request->filled('to')) { $version->whereDate('document_date', '<=', $request->string('to')); }
        });
        return $query->latest('updated_at');
    }

    public function index(Request $request)
    {
        if ($request->routeIs('validation.index')) {
            abort_unless($request->user()->is_admin && $request->user()->is_validator, 403);
            $request->merge(['status' => 'submitted', 'active' => false]);
        }
        return view('documents.index', ['documents' => $this->query($request)->paginate(12)->withQueryString(), 'categories' => Category::orderBy('name')->get(), 'divisions' => $request->user()->is_admin ? Division::all() : collect()]);
    }

    public function create(Request $request)
    {
        abort_unless(! $request->user()->is_admin && $request->user()->division_id, 403);
        return view('documents.form', ['document' => null, 'version' => null, 'categories' => Category::where('is_active', true)->orderBy('name')->get()]);
    }

    public function edit(Request $request, Document $document, ArchiveWorkflow $workflow)
    {
        $workflow->assertOwner($request->user(), $document);
        $version = $document->latestVersion;
        abort_unless($version->editable, 409, 'Versi ini terkunci. Buat revisi setelah keputusan.');
        return view('documents.form', ['document' => $document, 'version' => $version->load('files'), 'categories' => Category::where('is_active', true)->orWhere('id', $version->category_id)->orderBy('name')->get()]);
    }

    public function store(Request $request, ArchiveWorkflow $workflow) { return $this->persist($request, $workflow); }
    public function update(Request $request, Document $document, ArchiveWorkflow $workflow) { return $this->persist($request, $workflow, $document); }

    private function persist(Request $request, ArchiveWorkflow $workflow, ?Document $document = null)
    {
        if ($document) { $workflow->assertOwner($request->user(), $document); }
        $uploadRules = ['nullable', File::types(config('archive.extensions'))->max(config('archive.max_file_kb')), 'extensions:'.implode(',', config('archive.extensions'))];
        $data = $request->validate([
            'title' => 'required|string|max:255', 'document_number' => 'nullable|string|max:255', 'document_date' => 'required|date',
            'category_id' => ['required', Rule::exists('categories', 'id')->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $document?->latestVersion?->category_id ?? 0))],
            'description' => 'nullable|string|max:10000', 'keywords' => 'nullable|string|max:255', 'physical_location' => 'nullable|string|max:255',
            'revision_note' => 'nullable|string|max:5000', 'main_file' => $uploadRules,
            'attachments' => 'nullable|array|max:'.config('archive.max_attachments'), 'attachments.*' => $uploadRules,
            'expected_attachments' => 'required_with:attachments|nullable|integer|min:0|max:'.config('archive.max_attachments'),
            'remove_files' => 'nullable|array', 'remove_files.*' => 'integer|distinct',
            'version_id' => $document ? 'required|integer' : 'nullable',
        ]);
        if ((int) ($data['expected_attachments'] ?? 0) !== count($request->file('attachments', []))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['attachments' => 'Sebagian lampiran tidak diterima server. Periksa batas unggahan hosting dan pilih kembali seluruh berkas.']);
        }
        $metadata = \Illuminate\Support\Arr::only($data, ['title', 'document_number', 'document_date', 'category_id', 'description', 'keywords', 'physical_location', 'revision_note']);
        $document = $workflow->save($request->user(), $metadata, $document, $request->file('main_file'), $request->file('attachments', []), $data['remove_files'] ?? [], $data['version_id'] ?? null);
        $duplicate = ! empty($metadata['document_number']) && DocumentVersion::where('document_number', $metadata['document_number'])->whereHas('document', fn ($q) => $q->where('division_id', $document->division_id)->where('id', '!=', $document->id))->exists();
        return redirect()->route('documents.show', $document)->with('success', 'Draf berhasil disimpan.')->with('notice', $duplicate ? 'Nomor dokumen ini juga digunakan arsip lain di divisi Anda. Periksa kemungkinan duplikasi.' : null);
    }

    public function show(Request $request, Document $document)
    {
        abort_unless($request->user()->canAccess($document), 403);
        $version = $request->filled('version') ? $document->versions()->whereKey($request->query('version'))->firstOrFail() : $document->latestVersion;
        return view('documents.show', ['document' => $document->load('division'), 'version' => $version->load(['files', 'category', 'creator', 'reviewer', 'reviews.user']), 'versions' => $document->versions()->orderByDesc('number')->get(), 'activities' => $document->activities()->with('user')->latest()->paginate(15)]);
    }

    public function submit(Request $request, Document $document, ArchiveWorkflow $workflow)
    {
        $workflow->submit($request->user(), $document);
        return back()->with('success', 'Dokumen diajukan kepada Ketua.');
    }
    public function withdraw(Request $request, Document $document, ArchiveWorkflow $workflow)
    {
        $workflow->withdraw($request->user(), $document);
        return back()->with('success', 'Pengajuan ditarik menjadi draf.');
    }
    public function revise(Request $request, Document $document, ArchiveWorkflow $workflow)
    {
        $workflow->revise($request->user(), $document);
        return redirect()->route('documents.edit', $document)->with('success', 'Draf versi baru dibuat. Lengkapi catatan revisi sebelum diajukan.');
    }
    public function review(Request $request, DocumentVersion $version, ArchiveWorkflow $workflow)
    {
        $data = $request->validate(['decision' => ['required', Rule::in(['approved', 'revision', 'rejected'])], 'note' => 'nullable|string|max:5000', 'submission_token' => 'required|uuid']);
        $workflow->review($request->user(), $version, $data['decision'], $data['note'] ?? null, $data['submission_token']);
        return redirect()->route('documents.show', $version->document_id)->with('success', 'Keputusan validasi berhasil dicatat.');
    }

    public function file(Request $request, DocumentFile $file)
    {
        $document = $file->version->document;
        abort_unless($request->user()->canAccess($document), 403);
        $disk = Storage::disk('archive');
        abort_unless($disk->exists($file->path), 404, 'Berkas tidak tersedia. Hubungi administrator.');
        $inline = $request->boolean('preview') && in_array($file->mime, ['application/pdf', 'image/jpeg', 'image/png'], true);
        Activity::record($request->user(), $inline ? 'Pratinjau berkas' : 'Berkas diunduh', $document, ['file' => $file->id, 'version' => $file->version->number]);
        return response()->file($disk->path($file->path), ['Content-Type' => $file->mime, 'Content-Disposition' => \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition($inline ? 'inline' : 'attachment', $file->original_name, 'dokumen.'.pathinfo($file->original_name, PATHINFO_EXTENSION)), 'X-Content-Type-Options' => 'nosniff', 'X-Frame-Options' => 'SAMEORIGIN', 'Cache-Control' => 'private, no-store', 'Content-Security-Policy' => "sandbox; default-src 'none'; frame-ancestors 'self'"]);
    }

    public function export(Request $request)
    {
        $query = $this->query($request);
        Activity::record($request->user(), 'Daftar arsip diekspor');
        return response()->streamDownload(function () use ($query, $request) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Kode Arsip', 'Judul', 'Divisi', 'Kategori', 'Nomor Dokumen', 'Tanggal Dokumen', 'Status', 'Versi'], ',', '"', '');
            foreach ($query->lazy(100) as $document) {
                $version = $request->boolean('active') ? $document->activeVersion : $document->latestVersion;
                $row = [$document->code, $version->title, $document->division->name, $version->category->name, $version->document_number, $version->document_date->format('Y-m-d'), $version->status_label, $version->number];
                $row = array_map(fn ($cell) => preg_match('/^[\s]*[=+@-]/u', (string) $cell) ? "'".$cell : $cell, $row);
                fputcsv($stream, $row, ',', '"', '');
            }
            fclose($stream);
        }, 'arsipku-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
