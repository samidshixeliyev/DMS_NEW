@extends('layouts.app')

@section('title', 'İdarələr')

@section('content')
<div class="page-header">
    <h2><i class="bi bi-diagram-3 me-2"></i>İdarələr</h2>
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
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div style="overflow-x:auto;">
            <table class="table table-hover table-bordered mb-0" id="departmentsTable" style="width:100%">
                <thead>
                    <tr>
                        <th style="background:#1e3a5f;color:#fff;text-align:center;width:60px;">#</th>
                        <th style="background:#1e3a5f;color:#fff;text-align:center;">Ad</th>
                        <th style="background:#374151;color:#fff;text-align:center;width:150px;">Əməliyyat</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Yarat Modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('departments.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>İdarə yarat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ad <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required autofocus>
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

{{-- Redaktə Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>İdarəni redaktə et</h5>
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

{{-- Bax Modal --}}
<div class="modal fade" id="showModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>İdarə məlumatları</h5>
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
document.addEventListener('DOMContentLoaded', function () {
    var table = $('#departmentsTable').DataTable({
        processing: true,
        serverSide: true,
        searching: true,
        ajax: {
            url: "{{ route('departments.load') }}",
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        },
        columns: [
            { data: 'rowNum', className: 'text-center', orderable: false },
            { data: 'name' },
            {
                data: null, orderable: false, searchable: true, render: function (d) {
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
        order: [[1, 'asc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        dom: '<"d-flex justify-content-between align-items-center flex-wrap px-3 pt-2"l>rt<"d-flex justify-content-between align-items-center flex-wrap px-3 pb-2"ip>',
        language: {
            paginate: { previous: "&laquo;", next: "&raquo;" },
            emptyTable: "İdarə tapılmadı",
            info: "_START_-_END_ / _TOTAL_",
            infoEmpty: "Məlumat yoxdur",
            lengthMenu: "_MENU_ nəticə",
            processing: "Yüklənir...",
            zeroRecords: "Tapılmadı",
            search: "Axtar:"
        }
    });
});

async function showDetails(id) {
    var data = await fetchJson('/departments/' + id);
    if (!data) return;
    document.getElementById('showModalBody').innerHTML =
        '<table class="table table-bordered detail-table mb-0">'
        + '<tr><th width="35%">ID</th><td>' + escapeHtml(String(data.id)) + '</td></tr>'
        + '<tr><th>Ad</th><td>' + escapeHtml(data.name) + '</td></tr>'
        + '<tr><th>Yaradılıb</th><td>' + escapeHtml(data.created_at || '-') + '</td></tr>'
        + '</table>';
    new bootstrap.Modal(document.getElementById('showModal')).show();
}

async function editRecord(id) {
    var data = await fetchJson('/departments/' + id + '/edit');
    if (!data) return;
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('editForm').action = '/departments/' + id;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function deleteRecord(id) {
    if (confirm('Bu idarəni silmək istədiyinizdən əminsiniz?')) {
        var form = document.getElementById('deleteForm');
        form.action = '/departments/' + id;
        form.submit();
    }
}

@if($errors->any() && old('_token'))
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('createModal')).show();
});
@endif
</script>
@endpush