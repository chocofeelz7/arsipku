<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Division;
use App\Models\Document;
use App\Models\User;
use App\Services\ArchiveWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAndDivisionTest extends TestCase
{
    use RefreshDatabase;

    private User $chair;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('profiles');
        $this->seed();
        $this->chair = User::factory()->create(['is_admin' => true, 'is_validator' => true, 'must_change_password' => false]);
        $this->owner = User::factory()->create(['division_id' => Division::first()->id, 'must_change_password' => false]);
    }

    private function photo(string $name = 'foto.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aFAAAAABJRU5ErkJggg=='));
    }

    public function test_photo_upload_replaces_only_own_photo_and_removes_old_file(): void
    {
        $this->actingAs($this->owner)->put(route('profile.update'), ['photo' => $this->photo(), 'user_id' => $this->chair->id, 'is_admin' => true])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));
        $firstPath = $this->owner->fresh()->profile_photo_path;
        $this->assertNotNull($firstPath);
        Storage::disk('profiles')->assertExists($firstPath);
        $this->assertNull($this->chair->fresh()->profile_photo_path);
        $this->assertFalse($this->owner->fresh()->is_admin);
        $this->owner->refresh();
        $this->get(route('profile.edit'))->assertOk()->assertSee('Hapus Foto')->assertSee('Faiq Nurraihan');
        $this->get(route('profile.photo'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Content-Type', 'image/png');

        $this->put(route('profile.update'), ['photo' => $this->photo('baru.png')])->assertSessionHasNoErrors();
        $secondPath = $this->owner->fresh()->profile_photo_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('profiles')->assertMissing($firstPath);
        Storage::disk('profiles')->assertExists($secondPath);
        $this->assertDatabaseHas('activities', ['user_id' => $this->owner->id, 'action' => 'Foto profil diperbarui']);
    }

    public function test_photo_is_private_and_another_account_cannot_fetch_it_by_query_parameter(): void
    {
        $this->actingAs($this->chair)->put(route('profile.update'), ['photo' => $this->photo()])->assertSessionHasNoErrors();
        $this->actingAs($this->owner)->get(route('profile.photo', ['user_id' => $this->chair->id]))->assertNotFound();
        $this->get(route('profile.edit'))->assertOk()->assertDontSee('Hapus Foto');
        $this->post(route('logout'));
        $this->get(route('profile.photo'))->assertRedirect(route('login'));
        $this->put(route('profile.update'), ['photo' => $this->photo()])->assertRedirect(route('login'));
        $this->delete(route('profile.destroy'))->assertRedirect(route('login'));
    }

    public function test_invalid_or_oversized_photos_preserve_existing_photo(): void
    {
        $this->actingAs($this->owner)->put(route('profile.update'), ['photo' => $this->photo()])->assertSessionHasNoErrors();
        $path = $this->owner->fresh()->profile_photo_path;
        $invalidFiles = [
            UploadedFile::fake()->createWithContent('foto.png', '<?php echo "not an image";'),
            UploadedFile::fake()->createWithContent('foto.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            $this->photo('foto.php'),
            $this->photo()->size(2049),
        ];
        foreach ($invalidFiles as $file) {
            $this->put(route('profile.update'), ['photo' => $file])->assertSessionHasErrors('photo');
            $this->assertSame($path, $this->owner->fresh()->profile_photo_path);
        }
        $this->put(route('profile.update'))->assertSessionHasErrors('photo');
        $this->assertCount(1, Storage::disk('profiles')->allFiles());
    }

    public function test_user_can_remove_photo_and_return_to_initials(): void
    {
        $this->actingAs($this->owner)->put(route('profile.update'), ['photo' => $this->photo()])->assertSessionHasNoErrors();
        $path = $this->owner->fresh()->profile_photo_path;
        $this->delete(route('profile.destroy'))->assertRedirect(route('profile.edit'));
        $this->assertNull($this->owner->fresh()->profile_photo_path);
        Storage::disk('profiles')->assertMissing($path);
        $this->get(route('profile.photo'))->assertNotFound();
    }

    public function test_initial_password_change_and_inactive_account_still_gate_profile_features(): void
    {
        $this->owner->update(['must_change_password' => true]);
        $this->actingAs($this->owner)->get(route('profile.edit'))->assertRedirect(route('password.edit'));
        $this->put(route('profile.update'), ['photo' => $this->photo()])->assertRedirect(route('password.edit'));
        $this->owner->update(['must_change_password' => false, 'is_active' => false]);
        $this->actingAs($this->owner)->put(route('profile.update'), ['photo' => $this->photo()])->assertRedirect(route('login'));
        $this->assertEmpty(Storage::disk('profiles')->allFiles());
    }

    public function test_chair_can_add_division_then_assign_one_active_leader(): void
    {
        $this->actingAs($this->chair)->post(route('admin.divisions.store'), ['code' => ' upt-tik ', 'name' => ' Unit Teknologi Informasi '])->assertSessionHasNoErrors()->assertRedirect(route('admin.divisions'));
        $division = Division::where('code', 'UPT-TIK')->firstOrFail();
        $this->assertSame('Unit Teknologi Informasi', $division->name);
        $this->get(route('admin.divisions'))->assertOk()->assertSee('UPT-TIK');
        $this->get(route('admin.users'))->assertOk()->assertSee('Unit Teknologi Informasi');
        $this->get(route('dashboard'))->assertOk()->assertSee('UPT-TIK');
        $payload = ['name' => 'Pimpinan TIK', 'email' => 'tik@arsipku.test', 'division_id' => $division->id, 'password' => 'KataSandiBaru2026', 'password_confirmation' => 'KataSandiBaru2026'];
        $this->post(route('admin.users.store'), $payload)->assertSessionHasNoErrors();
        $leader = User::where('email', 'tik@arsipku.test')->firstOrFail();
        $this->assertSame($division->id, $leader->division_id);
        $this->assertFalse($leader->is_admin);
        $this->post(route('admin.users.store'), [...$payload, 'email' => 'tik2@arsipku.test'])->assertSessionHasErrors('division_id');
        $this->assertDatabaseHas('activities', ['action' => 'Divisi ditambahkan', 'user_id' => $this->chair->id]);
        $this->assertDatabaseCount('divisions', 7);
    }

    public function test_non_admin_cannot_list_or_create_divisions(): void
    {
        $this->actingAs($this->owner)->get(route('admin.divisions'))->assertForbidden();
        $this->post(route('admin.divisions.store'), ['code' => 'BARU', 'name' => 'Divisi Baru'])->assertForbidden();
        $this->get(route('dashboard'))->assertDontSee(route('admin.divisions'));
        $this->assertDatabaseCount('divisions', 6);
    }

    public function test_division_codes_and_names_are_validated_before_save(): void
    {
        $this->actingAs($this->chair);
        foreach (['WAKIL-I', 'wakil-i', 'Kode Dengan Spasi', '<script>', str_repeat('A', 21), '-TIK', 'TIK-'] as $code) {
            $this->post(route('admin.divisions.store'), ['code' => $code, 'name' => 'Divisi Tambahan'])->assertSessionHasErrors('code');
        }
        $this->post(route('admin.divisions.store'), ['code' => 'BARU', 'name' => Division::first()->name])->assertSessionHasErrors('name');
        $this->post(route('admin.divisions.store'), ['code' => '', 'name' => ' '])->assertSessionHasErrors(['name', 'code']);
        $this->assertDatabaseCount('divisions', 6);
    }

    public function test_upgrade_migration_preserves_existing_accounts_documents_and_files(): void
    {
        Storage::fake('archive');
        $document = app(ArchiveWorkflow::class)->save(
            $this->owner,
            ['title' => 'Dokumen sebelum update', 'document_date' => '2026-10-18', 'category_id' => Category::first()->id],
            null,
            UploadedFile::fake()->createWithContent('lama.pdf', "%PDF-1.4\nArsip lama"),
            [],
        );
        $filePath = $document->latestVersion->files->first()->path;
        $passwordHash = $this->owner->password;
        $migration = require database_path('migrations/2026_10_18_000200_add_profile_photo_path_to_users_table.php');
        $migration->down();
        $migration->up();

        $this->assertSame($passwordHash, $this->owner->fresh()->password);
        $this->assertNull($this->owner->fresh()->profile_photo_path);
        $this->assertSame('Dokumen sebelum update', Document::findOrFail($document->id)->latestVersion->title);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('divisions', 6);
        $this->assertDatabaseCount('documents', 1);
        Storage::disk('archive')->assertExists($filePath);
    }
}
