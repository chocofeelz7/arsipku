@props(['status'])
<span class="badge badge-{{ $status }}"><span class="badge-dot"></span>{{ \App\Models\DocumentVersion::LABELS[$status] ?? $status }}</span>
