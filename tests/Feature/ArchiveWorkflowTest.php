<?php

namespace Tests\Feature;

use App\Models\{Activity, Category, Division, Document, DocumentFile, DocumentVersion, InboxMessage, User};
use App\Services\ArchiveWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $chair;
    private User $owner;
    private User $other;
    private Category $category;
    private ArchiveWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('archive');
        $this->seed();
        $this->chair = User::factory()->create(['is_admin' => true, 'is_validator' => true, 'is_active' => true, 'must_change_password' => false]);
        $this->owner = User::factory()->create(['division_id' => Division::first()->id, 'is_active' => true, 'must_change_password' => false]);
        $this->other = User::factory()->create(['division_id' => Division::skip(1)->first()->id, 'is_active' => true, 'must_change_password' => false]);
        $this->category = Category::first();
        $this->workflow = app(ArchiveWorkflow::class);
    }

    private function metadata(string $title = 'Dokumen Pengujian'): array
    {
        return ['title' => $title, 'document_date' => '2026-10-18', 'category_id' => $this->category->id, 'document_number' => 'TEST/001', 'revision_note' => 'Catatan revisi pengujian'];
    }

    private function upload(string $name, int $kb): UploadedFile
    {
        // Real bytes exercise the same size and checksum checks as production.
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n".str_repeat('x', $kb * 1024 - 9));
    }

    private function document(?User $actor = null, string $title = 'Dokumen Pengujian'): Document
    {
        return $this->workflow->save($actor ?? $this->owner, $this->metadata($title), null, $this->upload('utama.pdf', 10), [$this->upload('lampiran-a.pdf', 5), $this->upload('lampiran-b.pdf', 5)]);
    }

    public function test_login_and_all_principal_pages_render(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->document();
        foreach (['/dashboard', '/dokumen', '/dokumen?active=1', '/validasi', '/administrasi/pengguna', '/administrasi/kategori', '/aktivitas', '/notifikasi', '/kata-sandi'] as $url) {
            $this->actingAs($this->chair)->get($url)->assertOk();
        }
        $this->actingAs($this->owner)->get('/dokumen/create')->assertOk();
    }

    public function test_draft_with_multiple_attachments_becomes_active_only_after_chair_approval(): void
    {
        $document = $this->document();
        $this->assertCount(3, $document->latestVersion->files);
        $this->assertNull($document->active_version_id);
        $this->actingAs($this->owner)->post(route('documents.submit', $document))->assertRedirect();
        $version = $document->fresh()->latestVersion;
        $this->assertSame('submitted', $version->status);
        $this->assertDatabaseHas('inbox_messages', ['user_id' => $this->chair->id, 'document_id' => $document->id]);
        $this->actingAs($this->chair)->post(route('versions.review', $version), ['decision' => 'approved', 'submission_token' => $version->submission_token])->assertRedirect();
        $this->assertSame($version->id, $document->fresh()->active_version_id);
        $this->assertDatabaseHas('reviews', ['document_version_id' => $version->id, 'decision' => 'approved']);
        $this->assertDatabaseHas('inbox_messages', ['user_id' => $this->owner->id, 'document_id' => $document->id]);
    }

    public function test_cross_division_metadata_mutations_and_files_are_forbidden(): void
    {
        $document = $this->document();
        $file = $document->latestVersion->files->last();
        $this->actingAs($this->other);
        foreach ([route('documents.show', $document), route('documents.edit', $document), route('files.show', $file), route('files.show', ['file' => $file, 'preview' => 1])] as $url) { $this->get($url)->assertForbidden(); }
        foreach (['documents.submit', 'documents.withdraw', 'documents.revise'] as $route) { $this->post(route($route, $document))->assertForbidden(); }
        $this->put(route('documents.update', $document), [...$this->metadata(), 'version_id' => $document->latestVersion->id])->assertForbidden();
    }

    public function test_search_export_dashboard_and_notifications_do_not_leak_foreign_division(): void
    {
        $own = $this->document($this->owner, 'RAHASIA-AKADEMIK');
        $foreign = $this->document($this->other, 'RAHASIA-KEUANGAN');
        InboxMessage::create(['user_id' => $this->owner->id, 'document_id' => $foreign->id, 'message' => 'RAHASIA-KEUANGAN']);
        $this->actingAs($this->owner);
        $this->get('/dashboard')->assertSee('RAHASIA-AKADEMIK')->assertDontSee('RAHASIA-KEUANGAN');
        $this->get('/dokumen?division='.$foreign->division_id)->assertSee('RAHASIA-AKADEMIK')->assertDontSee('RAHASIA-KEUANGAN');
        $this->get('/dokumen?q=RAHASIA-KEUANGAN')->assertDontSee('RAHASIA-KEUANGAN</a>', false)->assertSee('0 dokumen');
        $this->get('/notifikasi')->assertDontSee('RAHASIA-KEUANGAN');
        $csv = $this->get('/dokumen/ekspor')->assertOk()->streamedContent();
        $this->assertStringContainsString('RAHASIA-AKADEMIK', $csv);
        $this->assertStringNotContainsString('RAHASIA-KEUANGAN', $csv);
    }

    public function test_owner_can_download_own_attachment_and_chair_can_access_all_files(): void
    {
        $document = $this->document();
        $file = $document->latestVersion->files->last();
        $this->actingAs($this->owner)->get(route('files.show', $file))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->chair)->get(route('files.show', ['file' => $file, 'preview' => 1]))->assertOk();
        $this->assertDatabaseHas('activities', ['document_id' => $document->id, 'action' => 'Berkas diunduh']);
    }

    public function test_submitted_document_is_locked_against_edits(): void
    {
        $document = $this->document();
        $this->workflow->submit($this->owner, $document);
        $this->actingAs($this->owner)->put(route('documents.update', $document), [...$this->metadata('Tidak boleh mengganti'), 'version_id' => $document->latestVersion->id])->assertStatus(409);
        $this->assertSame('Dokumen Pengujian', $document->fresh()->latestVersion->title);
    }

    public function test_revision_or_rejection_requires_a_note_and_non_validator_cannot_decide(): void
    {
        $document = $this->document();
        $this->workflow->submit($this->owner, $document);
        $version = $document->fresh()->latestVersion;
        $payload = ['decision' => 'revision', 'submission_token' => $version->submission_token];
        $this->actingAs($this->chair)->post(route('versions.review', $version), $payload)->assertSessionHasErrors('note');
        $this->actingAs($this->owner)->post(route('versions.review', $version), [...$payload, 'note' => 'Coba validasi sendiri'])->assertForbidden();
        $this->actingAs($this->owner)->get('/validasi')->assertForbidden();
        $this->assertSame('submitted', $version->fresh()->status);
    }

    public function test_revision_can_be_resubmitted_with_decision_history_preserved(): void
    {
        $document = $this->document();
        $this->workflow->submit($this->owner, $document);
        $version = $document->fresh()->latestVersion;
        $this->workflow->review($this->chair, $version, 'revision', 'Lengkapi lampiran.');
        $this->workflow->save($this->owner, $this->metadata('Judul diperbaiki'), $document, null, []);
        $this->workflow->submit($this->owner, $document);
        $this->workflow->review($this->chair, $version, 'approved', null);
        $this->assertCount(2, $version->fresh()->reviews);
        $this->assertSame('approved', $version->fresh()->status);
    }

    public function test_new_version_does_not_replace_active_version_or_remove_old_attachments(): void
    {
        $document = $this->document();
        $this->workflow->submit($this->owner, $document);
        $old = $document->fresh()->latestVersion;
        $this->workflow->review($this->chair, $old, 'approved', null);
        $this->workflow->revise($this->owner, $document);
        $new = $document->fresh()->latestVersion;
        $removed = $new->files()->where('kind', 'attachment')->first();
        $this->workflow->save($this->owner, $this->metadata('Versi baru'), $document, $this->upload('baru.pdf', 8), [], [$removed->id], $new->id);
        $this->assertSame($old->id, $document->fresh()->active_version_id);
        $this->assertCount(3, $old->fresh()->files);
        $this->assertCount(2, $new->fresh()->files);
        Storage::disk('archive')->assertExists($removed->path);
        $this->workflow->submit($this->owner, $document);
        $this->workflow->review($this->chair, $new, 'rejected', 'Belum memenuhi syarat.');
        $this->assertSame($old->id, $document->fresh()->active_version_id);
        $this->actingAs($this->owner)->get('/dokumen?active=1')->assertSee('Dokumen Pengujian')->assertDontSee('Versi baru');
    }

    public function test_stale_review_after_withdraw_and_resubmit_is_rejected(): void
    {
        $document = $this->document();
        $this->workflow->submit($this->owner, $document);
        $version = $document->fresh()->latestVersion;
        $oldToken = $version->submission_token;
        $this->workflow->withdraw($this->owner, $document);
        $this->workflow->save($this->owner, $this->metadata('Isi telah berubah'), $document, null, []);
        $this->workflow->submit($this->owner, $document);
        $this->actingAs($this->chair)->post(route('versions.review', $version), ['decision' => 'approved', 'submission_token' => $oldToken])->assertStatus(409);
        $this->assertNull($document->fresh()->active_version_id);
    }

    public function test_second_decision_is_rejected(): void
    {
        $document = $this->document();
        $this->workflow->submit($this->owner, $document);
        $version = $document->fresh()->latestVersion;
        $this->workflow->review($this->chair, $version, 'approved', null);
        $this->actingAs($this->chair)->post(route('versions.review', $version), ['decision' => 'rejected', 'note' => 'Terlambat', 'submission_token' => $version->submission_token])->assertStatus(409);
        $this->assertCount(1, $version->reviews);
    }

    public function test_stale_edit_form_does_not_overwrite_new_version(): void
    {
        $document = $this->document();
        $this->workflow->submit($this->owner, $document);
        $old = $document->fresh()->latestVersion;
        $this->workflow->review($this->chair, $old, 'approved', null);
        $this->workflow->revise($this->owner, $document);
        $this->actingAs($this->owner)->put(route('documents.update', $document), [...$this->metadata('Form lama'), 'version_id' => $old->id])->assertStatus(409);
    }

    public function test_missing_main_or_missing_physical_file_blocks_submission(): void
    {
        $document = $this->workflow->save($this->owner, $this->metadata(), null, null, []);
        $this->actingAs($this->owner)->post(route('documents.submit', $document))->assertSessionHasErrors('main_file');
        $complete = $this->document();
        Storage::disk('archive')->delete($complete->latestVersion->files->first()->path);
        $this->post(route('documents.submit', $complete))->assertSessionHasErrors('main_file');
        $this->assertNull($complete->fresh()->active_version_id);
    }

    public function test_invalid_upload_and_package_limit_leave_no_partial_document(): void
    {
        $this->actingAs($this->owner)->post(route('documents.store'), [...$this->metadata(), 'main_file' => UploadedFile::fake()->create('bahaya.php', 1, 'text/x-php')])->assertSessionHasErrors('main_file');
        config(['archive.max_package_kb' => 1]);
        $this->post(route('documents.store'), [...$this->metadata(), 'main_file' => $this->upload('utama.pdf', 10)])->assertSessionHasErrors('attachments');
        $this->assertDatabaseCount('documents', 0);
        $this->assertEmpty(Storage::disk('archive')->allFiles());
    }

    public function test_file_from_another_version_cannot_be_removed(): void
    {
        $own = $this->document();
        $foreign = $this->document($this->other);
        $this->actingAs($this->owner)->put(route('documents.update', $own), [...$this->metadata(), 'version_id' => $own->latestVersion->id, 'remove_files' => [$foreign->latestVersion->files->last()->id]])->assertSessionHasErrors('attachments');
        $this->assertCount(3, $foreign->latestVersion->fresh()->files);
    }

    public function test_inactive_accounts_and_initial_password_restrictions_are_enforced(): void
    {
        $this->owner->update(['is_active' => false]);
        $this->actingAs($this->owner)->get('/dashboard')->assertRedirect('/login');
        $this->owner->update(['is_active' => true, 'must_change_password' => true]);
        $this->actingAs($this->owner)->get('/dokumen')->assertRedirect('/kata-sandi');
        $this->get('/kata-sandi')->assertOk();
    }

    public function test_division_cannot_manage_accounts_and_admin_cannot_overwrite_documents(): void
    {
        $document = $this->document();
        $this->actingAs($this->owner)->get('/administrasi/pengguna')->assertForbidden();
        $this->get('/administrasi/kategori')->assertForbidden();
        $this->actingAs($this->chair)->get('/dokumen/create')->assertForbidden();
        $this->put(route('documents.update', $document), $this->metadata())->assertForbidden();
    }

    public function test_csv_formula_is_neutralized_and_titles_are_html_escaped(): void
    {
        $this->document($this->owner, '=HYPERLINK("https://example.test")');
        $this->document($this->owner, '<script>alert(1)</script>');
        $this->actingAs($this->owner)->get('/dokumen')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $csv = $this->get('/dokumen/ekspor')->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }
}
