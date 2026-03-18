@extends('layouts.app')

@section('title', 'İcraçılar')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-people me-2"></i>İcraçılar</h2>
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

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div style="overflow-x:auto;">
            <table class="table table-hover table-bordered mb-0" id="executorsTable" style="width:100%">
                <thead>
                    <tr>
                        <th style="background:#1e3a5f;color:#fff;text-align:center;width:60px;">#</th>
                        <th style="background:#1e3a5f;color:#fff;text-align:center;">Ad</th>
                        <th style="background:#1e3a5f;color:#fff;text-align:center;">Vəzifə</th>
                        <th style="background:#1e3a5f;color:#fff;text-align:center;">İdarə</th>
                        <th style="background:#374151;color:#fff;text-align:center;width:150px;">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('executors.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Yeni icraçı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ad <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vəzifə <span class="text-danger">*</span></label>
                        <input type="text" name="position" class="form-control" value="{{ old('position') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İdarə <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" required>
                            <option value="">Seç</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                            @endforeach
                        </select>
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

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Redaktə et</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ad <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vəzifə <span class="text-danger">*</span></label>
                        <input type="text" name="position" id="edit_position" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">İdarə <span class="text-danger">*</span></label>
                        <select name="department_id" id="edit_department_id" class="form-select" required>
                            <option value="">Seç</option>
                        </select>
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

<div class="modal fade" id="showModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>İcraçı məlumatları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="showModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">@csrf @method('DELETE')</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('#executorsTable').DataTable({
        processing: true, serverSide: true,
        ajax: { url: "{{ route('executors.load') }}", type: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } },
        columns: [
            { data: 'rowNum', className: 'text-center', orderable: false },
            { data: 'name' },
            { data: 'position' },
            { data: 'department', render: function(d) { return d && d !== '-' ? '<span class="badge" style="background:var(--primary-light)">' + escapeHtml(d) + '</span>' : '-'; } },
            {
                data: null, orderable: false, searchable: false, render: function (d) {
                    var btns = '<div class="action-btns">';
                    btns += '<button class="btn btn-sm btn-info" title="Bax" onclick="showDetails(' + d.id + ')"><i class="bi bi-eye"></i></button>';
                    @if(in_array(auth()->user()->user_role, ['admin', 'manager']))
                    btns += '<button class="btn btn-sm btn-warning" title="Redaktə" onclick="editRecord(' + d.id + ')"><i class="bi bi-pencil"></i></button>';
                    @endif
                    @if(auth()->user()->user_role === 'admin')
                    btns += '<button class="btn btn-sm btn-danger" title="Sil" onclick="deleteRecord(' + d.id + ')"><i class="bi bi-trash"></i></button>';
                    @endif
                    return btns + '</div>';
                }
            }
        ],
        order: [[1, 'asc']], pageLength: 25, lengthMenu: [10, 25, 50, 100],
        dom: '<"d-flex justify-content-between align-items-center flex-wrap px-3 pt-2"l>rt<"d-flex justify-content-between align-items-center flex-wrap px-3 pb-2"ip>',
        language: { paginate: { previous: "&laquo;", next: "&raquo;" }, emptyTable: "İcraçı tapılmadı", info: "_START_-_END_ / _TOTAL_", infoEmpty: "Məlumat yoxdur", lengthMenu: "_MENU_ nəticə", processing: "Yüklənir...", zeroRecords: "Tapılmadı", search: "Axtar:" }
    });
});

async function showDetails(id) {
    var data = await fetchJson('/executors/' + id); if (!data) return;
    document.getElementById('showModalBody').innerHTML = '<table class="table table-bordered detail-table mb-0"><tr><th width="35%">ID</th><td>' + escapeHtml(String(data.id)) + '</td></tr><tr><th>Ad</th><td>' + escapeHtml(data.name) + '</td></tr><tr><th>Vəzifə</th><td>' + escapeHtml(data.position || '-') + '</td></tr><tr><th>İdarə</th><td>' + escapeHtml(data.department || '-') + '</td></tr><tr><th>Yaradılıb</th><td>' + escapeHtml(data.created_at || '-') + '</td></tr></table>';
    new bootstrap.Modal(document.getElementById('showModal')).show();
}

async function editRecord(id) {
    var data = await fetchJson('/executors/' + id + '/edit'); if (!data) return;
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_position').value = data.position || '';
    var select = document.getElementById('edit_department_id');
    select.innerHTML = '<option value="">Seç</option>';
    if (data.departments && Array.isArray(data.departments)) {
        data.departments.forEach(function(dept) {
            var option = document.createElement('option');
            option.value = dept.id;
            option.textContent = dept.name;
            if (dept.id == data.department_id) option.selected = true;
            select.appendChild(option);
        });
    }
    document.getElementById('editForm').action = '/executors/' + id;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteRecord(id) {
    if (confirm('Bu icraçını silmək istədiyinizdən əminsiniz?')) {
        var form = document.getElementById('deleteForm');
        form.action = '/executors/' + id;
        form.submit();
    }
}

@if($errors->any() && old('_token'))
document.addEventListener('DOMContentLoaded', function() { new bootstrap.Modal(document.getElementById('createModal')).show(); });
@endif
</script>
@endpush