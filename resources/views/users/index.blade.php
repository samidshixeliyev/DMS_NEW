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
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px">ID</th>
                            <th>Ad</th>
                            <th>Soyad</th>
                            <th>İstifadəçi adı</th>
                            <th>Rol</th>
                            <th>Rəhbər icraçı</th>
                            <th>Şöbə</th>
                            <th style="width: 150px">Əməliyyatlar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td><span class="badge bg-secondary">{{ $user->id }}</span></td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->surname }}</td>
                                <td><code>{{ $user->username }}</code></td>
                                <td>
                                    @if($user->user_role === 'admin')
                                        <span class="badge bg-danger">Admin</span>
                                    @elseif($user->user_role === 'manager')
                                        <span class="badge bg-primary">Menecer</span>
                                    @elseif($user->user_role === 'executor')
                                        <span class="badge bg-info">İcraçı</span>
                                    @else
                                        <span class="badge bg-secondary">İstifadəçi</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->executor)
                                        <span class="badge"
                                            style="background: var(--primary-light)">{{ $user->executor->name }}</span>
                                        @if($user->executor->department)
                                            <br><small class="text-muted">{{ $user->executor->department->name }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $user->department?->name ?: '-' }}</td>
                                <td>
                                    <div class="action-btns">
                                        <button type="button" class="btn btn-sm btn-info" title="Bax"
                                            onclick="showDetails({{ $user->id }})">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning" title="Redaktə et"
                                            onclick="editRecord({{ $user->id }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @if($user->id !== auth()->id())
                                            <button type="button" class="btn btn-sm btn-danger" title="Sil"
                                                onclick="deleteRecord({{ $user->id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="bi bi-person-gear d-block"></i>
                                        <p class="mb-0">İstifadəçi tapılmadı</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="p-3 border-top">
                    {{ $users->links() }}
                </div>
            @endif
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
                                <label class="form-label">İstifadəçi adı <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" value="{{ old('username') }}"
                                    required>
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
                                style="display: {{ old('user_role') === 'executor' ? 'block' : 'none' }};">
                                <label class="form-label">Rəhbər icraçı</label>
                                <select name="executor_id" class="form-select">
                                    <option value="">Seç (ixtiyari)</option>
                                    @foreach($executors as $executor)
                                        <option value="{{ $executor->id }}" {{ old('executor_id') == $executor->id ? 'selected' : '' }}>
                                            {{ $executor->name }}{{ $executor->department ? ' — ' . $executor->department->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Şöbə</label>
                                <select name="department_id" class="form-select">
                                    <option value="">Seç (ixtiyari)</option>
                                    @foreach(\App\Models\Department::active()->get() as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
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
                                <label class="form-label">İstifadəçi adı <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="edit_username" class="form-control" required>
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
                                <label class="form-label">Rəhbər icraçı</label>
                                <select name="executor_id" id="edit_executor_id" class="form-select">
                                    <option value="">Seç (ixtiyari)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Şöbə</label>
                                <select name="department_id" id="edit_department_id" class="form-select">
                                    <option value="">Seç (ixtiyari)</option>
                                </select>
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

        document.addEventListener('DOMContentLoaded', function () {
            var $cm = $('#createModal');
            $cm.find('select').each(function () {
                $(this).select2({ theme: 'bootstrap-5', dropdownParent: $cm.find('.modal-body'), placeholder: 'Seç', allowClear: true, width: '100%' });
            });

            $(document).on('change', 'select[name="executor_id"]', function () {
                var $opt = $(this).find(':selected');
                var $form = $(this).closest('form');
                var executorId = $(this).val();
                if (!executorId) return;

                var prefix = $form.attr('id') === 'editForm' ? 'edit' : 'create';
                if (prefix === 'edit') {
                    var deptId = null;
                    var select = document.getElementById('edit_executor_id');
                    var execData = select._execData || [];
                    execData.forEach(function (e) {
                        if (e.id == executorId && e.department) deptId = e.department.id;
                    });
                    if (deptId) $('#edit_department_id').val(deptId).trigger('change');
                } else {
                    @json($executors).forEach(function (e) {
                        if (e.id == executorId && e.department) {
                            $form.find('select[name="department_id"]').val(e.department.id).trigger('change');
                        }
                    });
                }
            });
        });

        function toggleExecutorFields(prefix) {
            var role = document.getElementById(prefix + '_user_role').value;
            var fields = document.querySelectorAll('.executor-fields-' + prefix);
            fields.forEach(function (el) {
                el.style.display = (role === 'executor') ? 'block' : 'none';
            });
        }

        async function showDetails(id) {
            const data = await fetchJson(`/users/${id}`);
            if (!data) return;

            document.getElementById('showModalBody').innerHTML = `
            <table class="table table-bordered detail-table mb-0">
                <tr><th width="35%">ID</th><td>${escapeHtml(String(data.id))}</td></tr>
                <tr><th>Ad</th><td>${escapeHtml(data.name)}</td></tr>
                <tr><th>Soyad</th><td>${escapeHtml(data.surname)}</td></tr>
                <tr><th>İstifadəçi adı</th><td>${escapeHtml(data.username)}</td></tr>
                <tr><th>Rol</th><td>${escapeHtml(roleLabels[data.user_role] || data.user_role)}</td></tr>
                <tr><th>Rəhbər icraçı</th><td>${escapeHtml(data.executor_name || '-')}</td></tr>
                <tr><th>Rəhbər icraçı idarəsi</th><td>${escapeHtml(data.executor_department || '-')}</td></tr>
                <tr><th>Şöbə</th><td>${escapeHtml(data.department_name || '-')}</td></tr>
                <tr><th>Yaradılma tarixi</th><td>${escapeHtml(data.created_at || '-')}</td></tr>
            </table>
        `;
            new bootstrap.Modal(document.getElementById('showModal')).show();
        }

        async function editRecord(id) {
            const data = await fetchJson(`/users/${id}/edit`);
            if (!data) return;

            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_surname').value = data.surname || '';
            document.getElementById('edit_username').value = data.username || '';
            document.getElementById('edit_user_role').value = data.user_role || 'user';
            var deptSelect = document.getElementById('edit_department_id');
            deptSelect.innerHTML = '<option value="">Seç (ixtiyari)</option>';
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
                    if (exec.id == data.executor_id) option.selected = true;
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

        @if($errors->any() && old('_token'))
            document.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('createModal')).show();
            });
        @endif
    </script>
@endpush