@props(['name', 'size' => 20])
@php($paths = [
'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
'folder' => '<path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/><path d="M3 10h18"/>',
'check' => '<path d="m5 12 4 4L19 6"/>',
'shield' => '<path d="m12 3 8 3v6c0 4-4 7-8 9-4-2-8-5-8-9V6Z"/><path d="m8 12 3 3 5-6"/>',
'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
'plus' => '<path d="M12 5v14M5 12h14"/>',
'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
'users' => '<circle cx="9" cy="7" r="4"/><path d="M2 21v-3a7 7 0 0 1 14 0v3M17 3a4 4 0 0 1 0 8M22 21v-3a7 7 0 0 0-4-6"/>',
'tag' => '<path d="M3 3h8l10 10-8 8L3 11Z"/><circle cx="7.5" cy="7.5" r="1"/>',
'logout' => '<path d="M9 4H4v16h5M10 12h11m-4-4 4 4-4 4"/>',
'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
'download' => '<path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5"/>',
'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>',
'edit' => '<path d="m16 3 5 5L9 20l-6 1 1-6ZM13 6l5 5"/>',
'upload' => '<path d="M12 16V3m-5 5 5-5 5 5M4 16v5h16v-5"/>',
'attachment' => '<path d="m8 13 7-7a3 3 0 0 1 4 4L9 20a5 5 0 0 1-7-7L13 2"/>',
])
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes }}>{!! $paths[$name] ?? $paths['file'] !!}</svg>
