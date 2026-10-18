@props(['user'])
<span {{ $attributes->class(['avatar']) }}>
    @if($user->profile_photo_path)
        <img src="{{ route('profile.photo') }}" alt="Foto profil {{ $user->name }}" width="36" height="36">
    @else
        <span aria-label="Inisial {{ $user->name }}">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
    @endif
</span>
