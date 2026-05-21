@extends('layouts.app')
@section('title', 'Elanlar')
@section('page-title', 'Elanlar İdarəetməsi')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-megaphone me-2"></i>Elanlar</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle me-1"></i> Yeni Elan
    </button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead>
                <tr style="background:#1e3a5f;color:#fff;">
                    <th style="width:40px">#</th>
                    <th>Başlıq</th>
                    <th>Mətn</th>
                    <th style="width:120px">Status</th>
                    <th style="width:130px">Yaradılıb</th>
                    <th style="width:110px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $ann)
                <tr>
                    <td class="text-center fw-semibold">{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $ann->title }}</td>
                    <td style="white-space:pre-wrap;max-width:400px;font-size:0.85rem">{{ Str::limit($ann->message, 120) }}</td>
                    <td class="text-center">
                        <button type="button"
                            class="btn btn-sm {{ $ann->is_active ? 'btn-success' : 'btn-secondary' }} toggle-btn"
                            data-id="{{ $ann->id }}" title="{{ $ann->is_active ? 'Aktiv — söndürmək üçün klik' : 'Deaktiv — aktiv etmək üçün klik' }}">
                            <i class="bi {{ $ann->is_active ? 'bi-eye' : 'bi-eye-slash' }} me-1"></i>
                            {{ $ann->is_active ? 'Aktiv' : 'Deaktiv' }}
                        </button>
                    </td>
                    <td class="text-center" style="font-size:0.8rem">
                        {{ $ann->created_at->format('d.m.Y H:i') }}<br>
                        <small class="text-muted">{{ $ann->creator?->name }}</small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-warning" title="Redaktə"
                            onclick="editAnn({{ $ann->id }}, {{ json_encode($ann->title) }}, {{ json_encode($ann->message) }}, {{ $ann->is_active ? 'true' : 'false' }})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('announcements.destroy', $ann) }}" method="POST" style="display:inline"
                            onsubmit="return confirm('Elanı silmək istəyirsiniz?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" title="Sil"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Heç bir elan tapılmadı.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('announcements.store') }}" method="POST">@csrf
                <div class="modal-header" style="background:#1e3a5f;color:#fff">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Yeni Elan</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Başlıq <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mətn <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="4" required maxlength="3000"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="create_is_active" value="1" checked>
                        <label class="form-check-label" for="create_is_active">Dərhal aktiv et (istifadəçilərə görünsün)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İmtina</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Yarat</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="POST">@csrf @method('PUT')
                <div class="modal-header" style="background:#1e3a5f;color:#fff">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Elanı Redaktə Et</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Başlıq <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="edit_title" class="form-control" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mətn <span class="text-danger">*</span></label>
                        <textarea name="message" id="edit_message" class="form-control" rows="4" required maxlength="3000"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                        <label class="form-check-label" for="edit_is_active">Aktiv (istifadəçilərə görünsün)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İmtina</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Yenilə</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function editAnn(id, title, message, isActive) {
    document.getElementById('editForm').action = '/announcements/' + id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_message').value = message;
    document.getElementById('edit_is_active').checked = isActive;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

document.querySelectorAll('.toggle-btn').forEach(function(btn) {
    btn.addEventListener('click', async function() {
        var id = this.dataset.id;
        try {
            var res = await fetch('/announcements/' + id + '/toggle', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            });
            var data = await res.json();
            location.reload();
        } catch(e) { alert('Xəta baş verdi'); }
    });
});
</script>
@endpush
