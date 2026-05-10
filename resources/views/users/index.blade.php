@extends('layouts.app')

@section('title', 'İstifadəçilər')

@section('content')
    <div class="page-header">
        <h2><i class="bi bi-person-gear me-2"></i>İstifadəçilər</h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-circle me-1"></i> Yeni istifadəçi
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
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
                <table class="table table-hover table-bordered mb-0" id="usersTable" style="width:100%">
                    <thead>
                        <tr>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;width:60px;">#</th>
                            <th style="background:#1e3a5f;color:#fff;">Ad</th>
                            <th style="background:#1e3a5f;color:#fff;">Soyad</th>
                            <th style="background:#1e3a5f;color:#fff;">İstifadəçi adı</th>
                            <th style="background:#1e3a5f;color:#fff;">E-poçt</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Rol</th>
                            <th style="background:#1e3a5f;color:#fff;">Rəhbər icraçı</th>
                            <th style="background:#1e3a5f;color:#fff;">İdarə</th>
                            <th style="background:#374151;color:#fff;text-align:center;width:130px;">Əməliyyat</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('users.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Yeni istifadəçi yarat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Ad <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Soyad <span class="text-danger">*</span></label>
                                <input type="text" name="surname" class="form-control" value="{{ old('surname') }}"
                                    required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">E-poçt <small class="text-muted">(ixtiyari — gələcəkdə bildiriş üçün)</small></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="example@domain.com" autocomplete="off">
                            </div>
                            <div class="col-12">
                                <label class="form-label">İstifadəçi adı <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="create_username" class="form-control" value="{{ old('username') }}" required autocomplete="off">
                                <div id="create_username_feedback" class="form-text"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Şifrə <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Şifrə təkrarı <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Rol <span class="text-danger">*</span></label>
                                <select name="user_role" id="create_user_role" class="form-select" required
                                    onchange="toggleExecutorFields('create')">
                                    <option value="">Seç</option>
                                    <option value="admin" {{ old('user_role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="manager" {{ old('user_role') === 'manager' ? 'selected' : '' }}>Menecer
                                    </option>
                                    <option value="user" {{ old('user_role') === 'user' ? 'selected' : '' }}>İstifadəçi
                                    </option>
                                    <option value="executor" {{ old('user_role') === 'executor' ? 'selected' : '' }}>İcraçı
                                    </option>
                                </select>
                            </div>
                            <div class="col-12 executor-fields-create"
                                style="display: {{ in_array(old('user_role'), ['executor', 'manager']) ? 'block' : 'none' }};">
                                <label class="form-label">
                                    Rəhbər icraçı
                                    <span class="text-danger exec-required-create" style="display:{{ old('user_role') === 'manager' ? 'inline' : 'none' }};"> *</span>
                                    <small class="text-muted exec-hint-create" style="display:{{ old('user_role') === 'manager' ? 'none' : 'inline' }};">(ixtiyari)</small>
                                </label>
                                <select name="executor_id" id="create_executor_id" class="form-select" {{ old('user_role') === 'manager' ? 'required' : '' }}>
                                    <option value="">Seç</option>
                                    @foreach($executors as $executor)
                                        <option value="{{ $executor->id }}" {{ old('executor_id') == $executor->id ? 'selected' : '' }}>
                                            {{ $executor->name }}{{ $executor->department ? ' — ' . $executor->department->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    İdarə
                                    <span class="text-danger dept-required-create" style="display:{{ old('user_role') === 'manager' ? 'inline' : 'none' }};"> *</span>
                                    <small class="text-muted dept-hint-create" style="display:{{ old('user_role') === 'manager' ? 'none' : 'inline' }};">(ixtiyari)</small>
                                    <small class="text-info dept-locked-create" style="display:none;"><i class="bi bi-lock-fill me-1"></i>Rəhbər icraçıdan avtomatik</small>
                                </label>
                                <select name="department_id" id="create_department_id" class="form-select"
                                    {{ old('user_role') === 'manager' ? 'required' : '' }}>
                                    <option value="">Seç</option>
                                    @foreach(\App\Models\Department::active()->get() as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                <div id="create_dept_head_warning" class="mt-1" style="display:none;"></div>
                            </div>
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
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>İstifadəçini redaktə et</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Ad <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Soyad <span class="text-danger">*</span></label>
                                <input type="text" name="surname" id="edit_surname" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">E-poçt <small class="text-muted">(ixtiyari — gələcəkdə bildiriş üçün)</small></label>
                                <input type="email" name="email" id="edit_email" class="form-control" placeholder="example@domain.com" autocomplete="off">
                            </div>
                            <div class="col-12">
                                <label class="form-label">İstifadəçi adı <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="edit_username" class="form-control" required autocomplete="off">
                                <div id="edit_username_feedback" class="form-text"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Yeni şifrə <small class="text-muted">(boş buraxın dəyişməmək
                                        üçün)</small></label>
                                <input type="password" name="password" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Şifrə təkrarı</label>
                                <input type="password" name="password_confirmation" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Rol <span class="text-danger">*</span></label>
                                <select name="user_role" id="edit_user_role" class="form-select" required
                                    onchange="toggleExecutorFields('edit')">
                                    <option value="admin">Admin</option>
                                    <option value="manager">Menecer</option>
                                    <option value="user">İstifadəçi</option>
                                    <option value="executor">İcraçı</option>
                                </select>
                            </div>
                            <div class="col-12 executor-fields-edit" style="display:none;">
                                <label class="form-label">
                                    Rəhbər icraçı
                                    <span class="text-danger exec-required-edit" style="display:none;"> *</span>
                                    <small class="text-muted exec-hint-edit">(ixtiyari)</small>
                                </label>
                                <select name="executor_id" id="edit_executor_id" class="form-select">
                                    <option value="">Seç</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    İdarə
                                    <span class="text-danger dept-required-edit" style="display:none;"> *</span>
                                    <small class="text-muted dept-hint-edit">(ixtiyari)</small>
                                    <small class="text-info dept-locked-edit" style="display:none;"><i class="bi bi-lock-fill me-1"></i>Rəhbər icraçıdan avtomatik</small>
                                </label>
                                <select name="department_id" id="edit_department_id" class="form-select">
                                    <option value="">Seç</option>
                                </select>
                                <div id="edit_dept_head_warning" class="mt-1" style="display:none;"></div>
                            </div>
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

    {{-- Show Modal --}}
    <div class="modal fade" id="showModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>İstifadəçi məlumatları</h5>
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
        const roleLabels = { admin: 'Admin', manager: 'Menecer', user: 'İstifadəçi', executor: 'İcraçı' };
        const roleBadge  = {
            admin:    '<span class="badge bg-danger">Admin</span>',
            manager:  '<span class="badge bg-primary">Menecer</span>',
            executor: '<span class="badge bg-info">İcraçı</span>',
            user:     '<span class="badge bg-secondary">İstifadəçi</span>',
        };

        var usersTable;

        document.addEventListener('DOMContentLoaded', function () {
            usersTable = $('#usersTable').DataTable({
                processing: true, serverSide: true,
                ajax: { url: "{{ route('users.load') }}", type: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken } },
                columns: [
                    { data: null, orderable: false, searchable: false, className: 'text-center',
                      render: function (d, t, r, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
                    { data: 'name' },
                    { data: 'surname' },
                    { data: 'username', render: function (d) { return '<code>' + escapeHtml(d) + '</code>'; } },
                    { data: 'email', render: function (d) {
                        if (!d) return '<span class="text-muted">-</span>';
                        return '<a href="mailto:' + escapeHtml(d) + '" class="text-decoration-none"><i class="bi bi-envelope me-1"></i>' + escapeHtml(d) + '</a>';
                    } },
                    { data: 'user_role', className: 'text-center',
                      render: function (d) { return roleBadge[d] || escapeHtml(d); } },
                    { data: null, orderable: false, searchable: false,
                      render: function (d) {
                          if (!d.executor) return '<span class="text-muted">-</span>';
                          var html = '<span class="badge" style="background:var(--primary-light)">' + escapeHtml(d.executor) + '</span>';
                          if (d.executorDept) html += '<br><small class="text-muted">' + escapeHtml(d.executorDept) + '</small>';
                          return html;
                      }},
                    { data: 'department', render: function (d) { return d ? escapeHtml(d) : '<span class="text-muted">-</span>'; } },
                    { data: null, orderable: false, searchable: false, className: 'text-center',
                      render: function (d) {
                          var h = '<div class="action-btns">';
                          h += '<button class="btn btn-sm btn-info" title="Bax" onclick="showDetails(' + d.id + ')"><i class="bi bi-eye"></i></button>';
                          h += '<button class="btn btn-sm btn-warning" title="Redaktə" onclick="editRecord(' + d.id + ')"><i class="bi bi-pencil"></i></button>';
                          if (!d.isSelf) h += '<button class="btn btn-sm btn-danger" title="Sil" onclick="deleteRecord(' + d.id + ')"><i class="bi bi-trash"></i></button>';
                          return h + '</div>';
                      }}
                ],
                order: [[0, 'asc']], pageLength: 25, lengthMenu: [10, 25, 50, 100],
                dom: '<"d-flex justify-content-between align-items-center flex-wrap px-3 pt-2"lf>rt<"d-flex justify-content-between align-items-center flex-wrap px-3 pb-2"ip>',
                language: { paginate: { previous: "&laquo;", next: "&raquo;" }, emptyTable: "İstifadəçi tapılmadı",
                            info: "_START_-_END_ / _TOTAL_", infoEmpty: "Məlumat yoxdur",
                            infoFiltered: "(ümumi _MAX_ qeyddən)", lengthMenu: "_MENU_ nəticə",
                            processing: "Yüklənir...", zeroRecords: "Tapılmadı", search: "Axtar:" }
            });

            // Sync role-dependent field state with any pre-filled old() values
            toggleExecutorFields('create');

            var $cm = $('#createModal');
            $cm.find('select').each(function () {
                $(this).select2({ theme: 'bootstrap-5', dropdownParent: $cm.find('.modal-body'), placeholder: 'Seç', allowClear: true, width: '100%' });
            });

            // Username uniqueness live check
            function debounce(fn, delay) {
                var t; return function () { clearTimeout(t); t = setTimeout(fn.bind(this, ...arguments), delay); };
            }
            function checkUsername(inputId, feedbackId, excludeId) {
                var val = document.getElementById(inputId).value.trim();
                var fb  = document.getElementById(feedbackId);
                if (!val) { fb.textContent = ''; fb.className = 'form-text'; return; }
                fetch('{{ route('users.check-username') }}?username=' + encodeURIComponent(val) + (excludeId ? '&exclude_id=' + excludeId : ''))
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (d.exists) {
                            fb.textContent = 'Bu istifadəçi adı artıq mövcuddur.';
                            fb.className = 'form-text text-danger';
                            document.getElementById(inputId).classList.add('is-invalid');
                        } else {
                            fb.textContent = 'İstifadəçi adı mövcud deyil.';
                            fb.className = 'form-text text-success';
                            document.getElementById(inputId).classList.remove('is-invalid');
                        }
                    });
            }
            document.getElementById('create_username').addEventListener('input', debounce(function () {
                checkUsername('create_username', 'create_username_feedback', null);
            }, 400));
            document.getElementById('edit_username').addEventListener('input', debounce(function () {
                var excludeId = (document.getElementById('editForm').action.match(/\/users\/(\d+)$/) || [])[1];
                checkUsername('edit_username', 'edit_username_feedback', excludeId);
            }, 400));
            // Clear feedback when modals close
            document.getElementById('createModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('create_username_feedback').textContent = '';
                document.getElementById('create_username').classList.remove('is-invalid');
                document.getElementById('create_dept_head_warning').style.display = 'none';
                lockDeptForExecutor('create', null);
            });
            document.getElementById('editModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('edit_username_feedback').textContent = '';
                document.getElementById('edit_username').classList.remove('is-invalid');
                document.getElementById('edit_dept_head_warning').style.display = 'none';
                lockDeptForExecutor('edit', null);
            });

            // Department head check — live AJAX warning
            function checkDeptHead(prefix, excludeId) {
                var role = document.getElementById(prefix + '_user_role').value;
                var deptId = document.getElementById(prefix + '_department_id').value;
                var warn = document.getElementById(prefix + '_dept_head_warning');
                if (role !== 'manager' || !deptId) { warn.style.display = 'none'; return; }
                var url = '{{ route('users.check-department-head') }}?department_id=' + encodeURIComponent(deptId)
                    + (excludeId ? '&exclude_id=' + excludeId : '');
                fetch(url).then(function (r) { return r.json(); }).then(function (d) {
                    if (d.exists) {
                        warn.innerHTML = '<div class="alert alert-warning py-1 px-2 mb-0" style="font-size:0.82rem">'
                            + '<i class="bi bi-exclamation-triangle-fill me-1"></i>'
                            + 'Bu şöbənin artıq aktiv rəhbəri var: <strong>' + escapeHtml(d.name) + '</strong>. '
                            + 'Saxladıqda əməliyyat bloklanacaq.</div>';
                        warn.style.display = 'block';
                    } else {
                        warn.style.display = 'none';
                    }
                });
            }

            $(document).on('change', '#create_department_id', function () {
                checkDeptHead('create', null);
            });
            $(document).on('change', '#create_user_role', function () {
                checkDeptHead('create', null);
            });
            $(document).on('change', '#edit_department_id', function () {
                var excludeId = (document.getElementById('editForm').action.match(/\/users\/(\d+)$/) || [])[1];
                checkDeptHead('edit', excludeId);
            });
            $(document).on('change', '#edit_user_role', function () {
                var excludeId = (document.getElementById('editForm').action.match(/\/users\/(\d+)$/) || [])[1];
                checkDeptHead('edit', excludeId);
            });

            function lockDeptForExecutor(prefix, deptId) {
                var $deptSelect = $('#' + prefix + '_department_id');
                var $lockedNote = $('.dept-locked-' + prefix);
                var $hint = $('.dept-hint-' + prefix);
                var hiddenId = prefix + '_dept_hidden';
                if (deptId) {
                    $deptSelect.val(deptId);
                    if ($deptSelect.data('select2')) $deptSelect.trigger('change.select2');
                    // Add hidden input so the locked value is still submitted when select is disabled
                    $('#' + hiddenId).remove();
                    $deptSelect.after('<input type="hidden" id="' + hiddenId + '" name="department_id" value="' + deptId + '">');
                    $deptSelect.prop('disabled', true);
                    $lockedNote.show();
                    $hint.hide();
                } else {
                    $deptSelect.prop('disabled', false);
                    $('#' + hiddenId).remove();
                    $lockedNote.hide();
                    $hint.show();
                }
            }

            $(document).on('change', 'select[name="executor_id"]', function () {
                var $form = $(this).closest('form');
                var executorId = $(this).val();
                var prefix = $form.attr('id') === 'editForm' ? 'edit' : 'create';

                if (!executorId) {
                    lockDeptForExecutor(prefix, null);
                    return;
                }

                var deptId = null;
                if (prefix === 'edit') {
                    var execData = document.getElementById('edit_executor_id')._execData || [];
                    execData.forEach(function (e) {
                        if (e.id == executorId && e.department) deptId = e.department.id;
                    });
                } else {
                    @json($executors).forEach(function (e) {
                        if (e.id == executorId && e.department) deptId = e.department.id;
                    });
                }
                lockDeptForExecutor(prefix, deptId);
            });

            // Re-apply lock state when edit modal is opened (executor already selected)
            $(document).on('shown.bs.modal', '#editModal', function () {
                var execId = $('#edit_executor_id').val();
                if (execId) {
                    var execData = document.getElementById('edit_executor_id')._execData || [];
                    var deptId = null;
                    execData.forEach(function (e) {
                        if (e.id == execId && e.department) deptId = e.department.id;
                    });
                    lockDeptForExecutor('edit', deptId);
                }
            });
        });

        function toggleExecutorFields(prefix) {
            var role = document.getElementById(prefix + '_user_role').value;
            var isExecutor        = (role === 'executor');
            var isManager         = (role === 'manager');
            var showExecutorField = isExecutor || isManager;

            // Executor field visible for both executor and manager roles
            document.querySelectorAll('.executor-fields-' + prefix).forEach(function (el) {
                el.style.display = showExecutorField ? 'block' : 'none';
            });

            // Clear executor_id only when switching to admin or user roles
            if (!showExecutorField) {
                var execSelect = document.getElementById(prefix + '_executor_id');
                if (execSelect) {
                    execSelect.value = '';
                    if ($(execSelect).data('select2')) $(execSelect).val('').trigger('change');
                }
            }

            // Executor field is required for manager role
            var execSelect = document.getElementById(prefix + '_executor_id');
            if (execSelect) execSelect.required = isManager;

            var execRequiredMark = document.querySelector('.exec-required-' + prefix);
            if (execRequiredMark) execRequiredMark.style.display = isManager ? 'inline' : 'none';

            var execHint = document.querySelector('.exec-hint-' + prefix);
            if (execHint) execHint.style.display = isManager ? 'none' : 'inline';

            // Department field is required for manager role
            var deptSelect = document.getElementById(prefix + '_department_id');
            if (deptSelect) deptSelect.required = isManager;

            var requiredMark = document.querySelector('.dept-required-' + prefix);
            if (requiredMark) requiredMark.style.display = isManager ? 'inline' : 'none';

            var hint = document.querySelector('.dept-hint-' + prefix);
            if (hint) hint.style.display = isManager ? 'none' : 'inline';
        }

        async function showDetails(id) {
            const data = await fetchJson(`/users/${id}`);
            if (!data) return;

            var emailCell = data.email
                ? `<a href="mailto:${escapeHtml(data.email)}" class="text-decoration-none"><i class="bi bi-envelope me-1"></i>${escapeHtml(data.email)}</a>`
                : '<span class="text-muted">-</span>';
            document.getElementById('showModalBody').innerHTML = `
            <table class="table table-bordered detail-table mb-0">
                <tr><th width="35%">ID</th><td>${escapeHtml(String(data.id))}</td></tr>
                <tr><th>Ad</th><td>${escapeHtml(data.name)}</td></tr>
                <tr><th>Soyad</th><td>${escapeHtml(data.surname)}</td></tr>
                <tr><th>E-poçt</th><td>${emailCell}</td></tr>
                <tr><th>İstifadəçi adı</th><td>${escapeHtml(data.username)}</td></tr>
                <tr><th>Rol</th><td>${escapeHtml(roleLabels[data.user_role] || data.user_role)}</td></tr>
                <tr><th>Rəhbər icraçı</th><td>${escapeHtml(data.executor_name || '-')}</td></tr>
                <tr><th>Rəhbər icraçı idarəsi</th><td>${escapeHtml(data.executor_department || '-')}</td></tr>
                <tr><th>İdarə</th><td>${escapeHtml(data.department_name || '-')}</td></tr>
                <tr><th>Yaradılma tarixi</th><td>${escapeHtml(data.created_at || '-')}</td></tr>
            </table>
        `;
            new bootstrap.Modal(document.getElementById('showModal')).show();
        }

        async function editRecord(id) {
            const data = await fetchJson(`/users/${id}/edit`);
            if (!data) return;

            document.getElementById('edit_name').value    = data.name    || '';
            document.getElementById('edit_surname').value = data.surname || '';
            document.getElementById('edit_email').value   = data.email   || '';
            document.getElementById('edit_username').value = data.username || '';
            document.getElementById('edit_user_role').value = data.user_role || 'user';
            var deptSelect = document.getElementById('edit_department_id');
            deptSelect.innerHTML = '<option value="">Seç</option>';
            if (data.departments && Array.isArray(data.departments)) {
                data.departments.forEach(function (dept) {
                    var option = document.createElement('option');
                    option.value = dept.id;
                    option.textContent = dept.name;
                    if (dept.id == data.department_id) option.selected = true;
                    deptSelect.appendChild(option);
                });
            }

            var select = document.getElementById('edit_executor_id');
            select.innerHTML = '<option value="">Seç (ixtiyari)</option>';
            if (data.executors && Array.isArray(data.executors)) {
                data.executors.forEach(function (exec) {
                    var dept = exec.department ? ' — ' + exec.department.name : '';
                    var option = document.createElement('option');
                    option.value = exec.id;
                    option.textContent = exec.name + dept;
                    // Pre-select executor for executor and manager roles
                    if (['executor', 'manager'].includes(data.user_role) && exec.id == data.executor_id) option.selected = true;
                    select.appendChild(option);
                });
            }

            document.getElementById('edit_executor_id')._execData = data.executors || [];
            toggleExecutorFields('edit');

            var $em = $('#editModal');
            $em.find('select').each(function () {
                if ($(this).data('select2')) $(this).select2('destroy');
                $(this).select2({ theme: 'bootstrap-5', dropdownParent: $em.find('.modal-body'), placeholder: 'Seç', allowClear: true, width: '100%' });
            });
            
            document.getElementById('editForm').action = `/users/${id}`;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function deleteRecord(id) {
            if (confirm('Bu istifadəçini silmək istədiyinizə əminsiniz?')) {
                const form = document.getElementById('deleteForm');
                form.action = `/users/${id}`;
                form.submit();
            }
        }

        @if($errors->any() && session('edit_user_id'))
            document.addEventListener('DOMContentLoaded', function () {
                editRecord({{ session('edit_user_id') }});
            });
        @elseif($errors->any() && old('_token'))
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('createModal')).show();
            });
        @endif
    </script>
@endpush