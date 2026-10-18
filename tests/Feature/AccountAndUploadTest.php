<?php

namespace Tests\Feature;

use App\Models\{Category, Division, Document, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Hash, Storage};
use Tests\TestCase;

class AccountAndUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $chair;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('archive'); $this->seed();
        $this->chair = User::factory()->create(['is_admin' => true, 'is_validator' => true, 'must_change_password' => false]);
        $this->owner = User::factory()->create(['division_id' => Division::first()->id, 'must_change_password' => false]);
    }

    private function payload(): array
    {
        return ['title' => 'Paket unggahan HTTP', 'document_date' => '2026-10-18', 'category_id' => Category::first()->id, 'main_file' => UploadedFile::fake()->createWithContent('utama.pdf', "%PDF-1.4\ncontoh utama"), 'attachments' => [UploadedFile::fake()->createWithContent('satu.pdf', "%PDF-1.4\nlampiran satu"), UploadedFile::fake()->createWithContent('dua.pdf', "%PDF-1.4\nlampiran dua")], 'expected_attachments' => 2];
    }

    public function test_http_upload_saves_all_files_and_cannot_forge_division(): void
    {
        $this->actingAs($this->owner)->post(route('documents.store'), [...$this->payload(), 'division_id' => Division::skip(1)->first()->id])->assertSessionHasNoErrors()->assertRedirect();
        $document = Document::firstOrFail();
        $this->assertSame($this->owner->division_id, $document->division_id);
        $this->assertCount(3, $document->latestVersion->files);
        $this->get(route('documents.edit', $document))->assertOk()->assertSee('satu.pdf')->assertSee('dua.pdf');
        $this->get(route('documents.show', $document))->assertOk()->assertSee('Paket unggahan HTTP');
    }

    public function test_partially_received_attachments_are_rejected_without_saving(): void
    {
        $payload = $this->payload(); $payload['expected_attachments'] = 3;
        $this->actingAs($this->owner)->post(route('documents.store'), $payload)->assertSessionHasErrors('attachments');
        $this->assertDatabaseCount('documents', 0);
        $this->assertEmpty(Storage::disk('archive')->allFiles());
    }

    public function test_chair_creates_only_division_role_and_cannot_duplicate_active_leader(): void
    {
        $division = Division::skip(1)->first();
        $payload = ['name' => 'Pimpinan Baru', 'email' => 'PIMPINAN@ARSIPKU.TEST', 'division_id' => $division->id, 'password' => 'KataSandiAwal!2026', 'password_confirmation' => 'KataSandiAwal!2026', 'is_admin' => true, 'is_validator' => true];
        $this->actingAs($this->chair)->post(route('admin.users.store'), $payload)->assertSessionHasNoErrors();
        $created = User::where('email', 'pimpinan@arsipku.test')->firstOrFail();
        $this->assertFalse($created->is_admin); $this->assertFalse($created->is_validator); $this->assertTrue($created->must_change_password);
        $this->post(route('admin.users.store'), [...$payload, 'email' => 'lain@arsipku.test'])->assertSessionHasErrors('division_id');
        $this->post(route('admin.users.toggle', $this->chair))->assertStatus(422);
    }

    public function test_deactivation_retains_documents_and_revokes_access(): void
    {
        $this->actingAs($this->owner)->post(route('documents.store'), $this->payload())->assertSessionHasNoErrors();
        $this->actingAs($this->chair)->post(route('admin.users.toggle', $this->owner))->assertRedirect();
        $this->assertFalse($this->owner->fresh()->is_active);
        $this->assertDatabaseCount('documents', 1);
        $this->actingAs($this->owner->fresh())->get('/dokumen')->assertRedirect('/login');
    }

    public function test_password_change_requires_current_password_and_clears_initial_restriction(): void
    {
        $this->owner->update(['password' => 'PasswordAwal!2026', 'must_change_password' => true]);
        $this->actingAs($this->owner)->put(route('password.update'), ['current_password' => 'salah', 'password' => 'PasswordBaru!2026', 'password_confirmation' => 'PasswordBaru!2026'])->assertSessionHasErrors('current_password');
        $this->put(route('password.update'), ['current_password' => 'PasswordAwal!2026', 'password' => 'PasswordBaru!2026', 'password_confirmation' => 'PasswordBaru!2026'])->assertRedirect(route('dashboard'));
        $this->assertFalse($this->owner->fresh()->must_change_password);
        $this->assertTrue(Hash::check('PasswordBaru!2026', $this->owner->fresh()->password));
    }

    public function test_login_uses_active_account_and_rate_limits_repeated_failures(): void
    {
        $this->owner->update(['password' => 'PasswordBenar!2026']);
        $this->post('/login', ['email' => $this->owner->email, 'password' => 'PasswordBenar!2026'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->owner);
        $this->post('/logout')->assertRedirect(route('login'));
        for ($i = 0; $i < 5; $i++) { $this->post('/login', ['email' => 'unknown@arsipku.test', 'password' => 'salah'])->assertRedirect(); }
        $this->post('/login', ['email' => 'unknown@arsipku.test', 'password' => 'salah'])->assertStatus(429);
    }

    public function test_inactive_category_is_not_available_for_new_document(): void
    {
        $category = Category::first(); $category->update(['is_active' => false]);
        $this->actingAs($this->owner)->post(route('documents.store'), $this->payload())->assertSessionHasErrors('category_id');
        $this->assertDatabaseCount('documents', 0);
    }
}
