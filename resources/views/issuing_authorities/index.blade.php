@extends('layouts.app')

@section('title', 'Kim qəbul edib')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-building-check me-2"></i>Kim qəbul edib</h2>
    @if(in_array(auth()->user()->user_role, ['admin', 'manager']))
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-circle me-1"></i> Yeni əlavə et
    </button>
    @endif
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 70px">ID</th>
                        <th>Ad</th>
                        <th style="width: 150px">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issuingAuthorities as $authority)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $authority->id }}</span></td>
                            <td>{{ $authority->name }}</td>
                            <td>
                                <div class="action-btns">
                                    <button type="button" class="btn btn-sm btn-info" title="Bax"
                                            onclick="showDetails({{ $authority->id }})">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if(in_array(auth()->user()->user_role, ['admin', 'manager']))
                                    <button type="button" class="btn btn-sm btn-warning" title="Redaktə"
                                            onclick="editRecord({{ $authority->id }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @endif
                                    @if(auth()->user()->user_role === 'admin')
                                    <button type="button" class="btn btn-sm btn-danger" title="Sil"
                                            onclick="deleteRecord({{ $authority->id }})">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="empty-state">
                                    <i class="bi bi-building-check d-block"></i>
                                    <p class="mb-0">Məlumat tapılmadı</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($issuingAuthorities->hasPages())
            <div class="p-3 border-top">
                {{ $issuingAuthorities->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('issuing-authorities.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Yeni əlavə et</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ad <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
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
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Redaktə et</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ad <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Ləğv et</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Yenilə</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Show Modal --}}
<div class="modal fade" id="showModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Orqan məlumatları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="showModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
async function showDetails(id) {
    const data = await fetchJson(`/issuing-authorities/${id}`);
    if (!data) return;
    
    document.getElementById('showModalBody').innerHTML = `
        <table class="table table-bordered detail-table mb-0">
            <tr><th width="35%">ID</th><td>${escapeHtml(String(data.id))}</td></tr>
            <tr><th>Ad</th><td>${escapeHtml(data.name)}</td></tr>
            <tr><th>Yaradılıb</th><td>${escapeHtml(data.created_at || '-')}</td></tr>
        </table>
    `;
    new bootstrap.Modal(document.getElementById('showModal')).show();
}

async function editRecord(id) {
    const data = await fetchJson(`/issuing-authorities/${id}/edit`);
    if (!data) return;
    
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('editForm').action = `/issuing-authorities/${id}`;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteRecord(id) {
    if (confirm('Bu orqanı silmək istədiyinizdən əminsiniz?')) {
        const form = document.getElementById('deleteForm');
        form.action = `/issuing-authorities/${id}`;
        form.submit();
    }
}
</script>
@endpush