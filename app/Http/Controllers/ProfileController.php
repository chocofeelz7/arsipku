<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
        ], [
            'photo.required' => 'Pilih foto profil terlebih dahulu.',
            'photo.image' => 'Berkas harus berupa gambar yang valid.',
            'photo.mimes' => 'Gunakan foto JPG, PNG, atau WebP.',
            'photo.extensions' => 'Ekstensi berkas harus JPG, JPEG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'photo.dimensions' => 'Dimensi foto maksimal 4096 × 4096 piksel.',
        ]);

        $newPath = $request->file('photo')->store('photos', 'profiles');

        try {
            $oldPath = DB::transaction(function () use ($request, $newPath): ?string {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $oldPath = $user->profile_photo_path;
                $user->forceFill(['profile_photo_path' => $newPath])->save();
                Activity::record($user, 'Foto profil diperbarui');

                return $oldPath;
            });
        } catch (Throwable $exception) {
            $this->deletePhoto($newPath);
            throw $exception;
        }

        $this->deletePhoto($oldPath);

        return to_route('profile.edit')->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $oldPath = DB::transaction(function () use ($request): ?string {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $oldPath = $user->profile_photo_path;
            $user->forceFill(['profile_photo_path' => null])->save();
            if ($oldPath) {
                Activity::record($user, 'Foto profil dihapus');
            }

            return $oldPath;
        });
        $this->deletePhoto($oldPath);

        return to_route('profile.edit')->with('success', 'Foto profil dihapus. Avatar kembali menggunakan inisial nama.');
    }

    public function photo(Request $request): StreamedResponse
    {
        $path = $request->user()->profile_photo_path;
        $disk = Storage::disk('profiles');
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'], 'inline');
    }

    private function deletePhoto(?string $path): void
    {
        if ($path) {
            try {
                Storage::disk('profiles')->delete($path);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
