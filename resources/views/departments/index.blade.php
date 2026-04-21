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
                        <th style="background:#1e3a5f;color:#fff;text-align:center;">Yuxarı idarə</th>
                        <th style="background:#1e3a5f;color:#fff;text-align:center;width:120px;">Tapşırıq verə bilər</th>
                        <th style="background:#374151;color:#fff;text-align:center;width:120px;">Əməliyyat</th>
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
                    <div class="mb-3">
                        <label class="form-label">Yuxarı idarə <small class="text-muted">(boş = üst səviyyə)</small></label>
                        <select name="parent_id" id="create_parent_id" class="form-select">
                            <option value="">— Yoxdur (üst səviyyə) —</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="can_assign" id="create_can_assign" value="1">
                            <label class="form-check-label" for="create_can_assign">
                                <i class="bi bi-send me-1"></i> Tapşırıq yaratmaq icazəsi
                                <small class="text-muted d-block">Bu idarə müstəqil tapşırıq verə bilər</small>
                            </label>
                        </div>
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
                    <div class="mb-3">
                        <label class="form-label">Yuxarı idarə <small class="text-muted">(boş = üst səviyyə)</small></label>
                        <select name="parent_id" id="edit_parent_id" class="form-select"></select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="can_assign" id="edit_can_assign" value="1">
                            <label class="form-check-label" for="edit_can_assign">
                                <i class="bi bi-send me-1"></i> Tapşırıq yaratmaq icazəsi
                            </label>
                        </div>
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
    // Populate parent_id select in create modal
    fetch('/departments?_select=1').then(function() {
        // We use the DataTable load endpoint instead
    });

    // Pre-populate create modal parent select from server, then apply select2
    loadDepartmentOptions('#create_parent_id', null, null, function() {
        $('#create_parent_id').select2({ theme: 'bootstrap-5', dropdownParent: $('#createModal'), placeholder: '— Yoxdur (üst səviyyə) —', allowClear: true, width: '100%' });
    });

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
            { data: 'parent', className: 'text-center' },
            { data: 'can_assign', className: 'text-center', orderable: false },
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

function loadDepartmentOptions(selectSelector, selectedId, excludeId, callback) {
    $.post("{{ route('departments.load') }}", {
        _token: csrfToken,
        start: 0,
        length: 1000,
        draw: 1
    }, function(res) {
        var $sel = $(selectSelector);
        $sel.empty().append('<option value="">— Yoxdur (üst səviyyə) —</option>');
        (res.data || []).forEach(function(d) {
            if (excludeId && d.id == excludeId) return;
            var sel = (selectedId && d.id == selectedId) ? ' selected' : '';
            $sel.append('<option value="' + d.id + '"' + sel + '>' + escapeHtml(d.name) + '</option>');
        });
        if (typeof callback === 'function') callback();
    });
}

async function showDetails(id) {
    var data = await fetchJson('/departments/' + id);
    if (!data) return;
    document.getElementById('showModalBody').innerHTML =
        '<table class="table table-bordered detail-table mb-0">'
        + '<tr><th width="40%">ID</th><td>' + escapeHtml(String(data.id)) + '</td></tr>'
        + '<tr><th>Ad</th><td>' + escapeHtml(data.name) + '</td></tr>'
        + '<tr><th>Yuxarı idarə</th><td>' + escapeHtml(data.parent_name || '—') + '</td></tr>'
        + '<tr><th>Tapşırıq icazəsi</th><td>' + (data.can_assign ? '<span class="badge bg-success">Bəli</span>' : '<span class="badge bg-secondary">Xeyr</span>') + '</td></tr>'
        + '<tr><th>Yaradılıb</th><td>' + escapeHtml(data.created_at || '-') + '</td></tr>'
        + '</table>';
    new bootstrap.Modal(document.getElementById('showModal')).show();
}

async function editRecord(id) {
    var data = await fetchJson('/departments/' + id + '/edit');
    if (!data) return;
    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_can_assign').checked = !!data.can_assign;
    document.getElementById('editForm').action = '/departments/' + id;

    // Populate parent select, excluding the department itself to prevent cycles
    var $sel = $('#edit_parent_id');
    if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
    $sel.empty().append('<option value="">— Yoxdur (üst səviyyə) —</option>');
    (data.all_departments || []).forEach(function(d) {
        var sel = (data.parent_id && d.id == data.parent_id) ? ' selected' : '';
        $sel.append('<option value="' + d.id + '"' + sel + '>' + escapeHtml(d.name) + '</option>');
    });
    $sel.select2({ theme: 'bootstrap-5', dropdownParent: $('#editModal'), placeholder: '— Yoxdur (üst səviyyə) —', allowClear: true, width: '100%' });

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
