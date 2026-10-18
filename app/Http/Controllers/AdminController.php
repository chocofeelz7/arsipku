<?php

namespace App\Http\Controllers;

use App\Models\{Activity, Category, Division, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    private function check(Request $request): void { abort_unless($request->user()->is_admin, 403); }
    public function divisions(Request $request): \Illuminate\Contracts\View\View
    {
        $this->check($request);

        return view('admin.divisions', ['divisions' => Division::orderBy('name')->get()]);
    }

    public function storeDivision(Request $request): \Illuminate\Http\RedirectResponse
    {
        $this->check($request);
        $request->merge([
            'code' => mb_strtoupper(trim((string) $request->input('code'))),
            'name' => trim((string) $request->input('name')),
        ]);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', 'unique:divisions,code'],
            'name' => ['required', 'string', 'max:255', 'unique:divisions,name'],
        ], [
            'code.regex' => 'Kode divisi hanya boleh berisi huruf, angka, dan tanda hubung di antara kata.',
            'code.unique' => 'Kode divisi sudah digunakan.',
            'name.unique' => 'Nama divisi sudah digunakan.',
        ]);
        DB::transaction(function () use ($request, $data): void {
            $division = Division::create($data);
            Activity::record($request->user(), 'Divisi ditambahkan', null, ['division_id' => $division->id, 'code' => $division->code, 'name' => $division->name]);
        });

        return to_route('admin.divisions')->with('success', 'Divisi ditambahkan. Buat akun pimpinannya melalui menu Pengguna.');
    }

    public function users(Request $request)
    {
        $this->check($request);
        return view('admin.users', ['users' => User::with('division')->orderByDesc('is_admin')->orderBy('name')->get(), 'divisions' => Division::all()]);
    }
    public function storeUser(Request $request)
    {
        $this->check($request);
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate(['name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users', 'division_id' => 'required|exists:divisions,id', 'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);
        DB::transaction(function () use ($request, $data) {
            Division::whereKey($data['division_id'])->lockForUpdate()->firstOrFail();
            if (User::where('division_id', $data['division_id'])->where('is_active', true)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['division_id' => 'Divisi sudah memiliki akun pimpinan aktif. Nonaktifkan akun lama terlebih dahulu.']);
            }
            $user = User::create([...\Illuminate\Support\Arr::except($data, 'password_confirmation'), 'email' => mb_strtolower($data['email']), 'is_active' => true, 'is_admin' => false, 'is_validator' => false, 'must_change_password' => true]);
            Activity::record($request->user(), 'Akun pimpinan dibuat', null, ['user_id' => $user->id, 'division_id' => $user->division_id]);
        });
        return back()->with('success', 'Akun pimpinan dibuat. Pengguna wajib mengganti kata sandi saat masuk pertama kali.');
    }
    public function toggleUser(Request $request, User $user)
    {
        $this->check($request);
        abort_if($user->id === $request->user()->id || $user->is_admin, 422, 'Akun Ketua tidak dapat dinonaktifkan melalui halaman ini.');
        DB::transaction(function () use ($request, $user) {
            Division::whereKey($user->division_id)->lockForUpdate()->firstOrFail();
            $user->refresh();
            if (! $user->is_active && User::where('division_id', $user->division_id)->where('is_active', true)->where('id', '!=', $user->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['division_id' => 'Pimpinan aktif lain sudah ditugaskan di divisi ini.']);
            }
            $user->update(['is_active' => ! $user->is_active, 'remember_token' => null]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            Activity::record($request->user(), $user->is_active ? 'Akun diaktifkan' : 'Akun dinonaktifkan', null, ['user_id' => $user->id]);
        });
        return back()->with('success', 'Status akun diperbarui.');
    }
    public function resetPassword(Request $request, User $user)
    {
        $this->check($request);
        abort_if($user->is_admin, 422, 'Gunakan menu kata sandi untuk akun Ketua.');
        $data = $request->validate(['password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);
        DB::transaction(function () use ($request, $user, $data) {
            $user->update(['password' => $data['password'], 'must_change_password' => true, 'remember_token' => null]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            Activity::record($request->user(), 'Kata sandi akun direset', null, ['user_id' => $user->id]);
        });
        return back()->with('success', 'Kata sandi sementara diperbarui. Pengguna wajib menggantinya saat masuk.');
    }
    public function categories(Request $request)
    {
        $this->check($request);
        return view('admin.categories', ['categories' => Category::orderBy('name')->get()]);
    }
    public function storeCategory(Request $request)
    {
        $this->check($request);
        $data = $request->validate(['name' => 'required|string|max:100|unique:categories']);
        $category = Category::create($data);
        Activity::record($request->user(), 'Kategori dibuat', null, ['category_id' => $category->id, 'name' => $category->name]);
        return back()->with('success', 'Kategori ditambahkan.');
    }
    public function toggleCategory(Request $request, Category $category)
    {
        $this->check($request);
        $category->update(['is_active' => ! $category->is_active]);
        Activity::record($request->user(), 'Status kategori diubah', null, ['category_id' => $category->id, 'active' => $category->is_active]);
        return back()->with('success', 'Status kategori diperbarui. Arsip lama tetap tersimpan.');
    }
}
