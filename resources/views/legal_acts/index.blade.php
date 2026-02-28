@extends('layouts.app')

@section('title', 'Hüquqi Aktlar')

@push('styles')
    <style>
        .row-partial td {
            background-color: #e0f2fe !important;
        }

        .row-partial:hover td {
            background-color: #bae6fd !important;
        }

        .row-partial td:first-child {
            box-shadow: inset 3px 0 0 #0284c7;
        }

        .row-overdue td {
            background-color: #fef2f2 !important;
        }

        .row-overdue:hover td {
            background-color: #fee2e2 !important;
        }

        .row-overdue td:first-child {
            box-shadow: inset 3px 0 0 #dc2626;
        }

        .row-warning td {
            background-color: #fefce8 !important;
        }

        .row-warning:hover td {
            background-color: #fef9c3 !important;
        }

        .row-warning td:first-child {
            box-shadow: inset 3px 0 0 #ca8a04;
        }

        .row-executed td {
            background-color: #f0fdf4 !important;
        }

        .row-executed:hover td {
            background-color: #dcfce7 !important;
        }

        .row-executed td:first-child {
            box-shadow: inset 3px 0 0 #16a34a;
        }

        .filter-row .flatpickr-input {
            background: #fff !important;
            cursor: pointer;
        }

        #legalActsTable thead th {
            text-align: center !important;
            vertical-align: middle !important;
            font-weight: 700;
            white-space: nowrap;
            color: #fff !important;
            border: 1px solid rgba(255, 255, 255, 0.18) !important;
        }

        #legalActsTable thead tr.band-header th {
            font-size: 0.82rem;
            padding: 0.6rem;
            letter-spacing: 0.3px;
        }

        #legalActsTable thead tr.sub-header th {
            font-size: 0.74rem;
            font-weight: 600;
            padding: 0.5rem 0.45rem;
        }

        th.bg-band-doc {
            background: #1e3a5f !important;
        }

        th.bg-band-doc-sub {
            background: #2a5298 !important;
        }

        th.bg-band-task {
            background: #065f46 !important;
        }

        th.bg-band-task-sub {
            background: #10a37f !important;
        }

        th.bg-band-exec {
            background: #5b21b6 !important;
        }

        th.bg-band-exec-sub {
            background: #7c4ddb !important;
        }

        th.bg-band-icra {
            background: #92400e !important;
        }

        th.bg-band-icra-sub {
            background: #d97706 !important;
        }

        th.bg-band-actions {
            background: #374151 !important;
        }

        #legalActsTable {
            min-width: 2100px;
        }

        #legalActsTable tbody td {
            font-size: 0.82rem;
            padding: 0.5rem 0.65rem;
            vertical-align: middle;
            text-align: center;
        }

        #legalActsTable tbody td.wrap-cell {
            white-space: normal;
            word-break: break-word;
            text-align: left;
            min-width: 180px;
            max-width: 280px;
        }

        #legalActsTable tbody tr:nth-child(even):not([class*="row-"]) {
            background-color: #f8fafc;
        }

        #legalActsTable td:last-child {
            white-space: nowrap;
            position: sticky;
            right: 0;
            background-color: #fff;
            box-shadow: -2px 0 4px rgba(0, 0, 0, 0.06);
            z-index: 999 !important;
        }

        .row-overdue td:last-child {
            background-color: #fef2f2 !important;
        }

        .row-warning td:last-child {
            background-color: #fefce8 !important;
        }

        .row-executed td:last-child {
            background-color: #f0fdf4 !important;
        }

        #legalActsTable tbody tr:nth-child(even):not([class*="row-"]) td:last-child {
            background-color: #f8fafc;
        }

        .action-btns {
            display: flex;
            flex-direction: column;
            gap: 4px;
            justify-content: center;
            align-items: center;
        }

        .action-btns .btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -1.65rem;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--accent, #3b82f6);
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px var(--accent, #3b82f6);
        }

        .timeline-item .tl-date {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 600;
        }

        .timeline-item .tl-user {
            font-size: 0.78rem;
            color: #64748b;
        }

        .timeline-item .tl-note {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1e293b;
            margin-top: 2px;
        }

        .timeline-item .tl-custom {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 2px;
            font-style: italic;
        }

        .dt-buttons .btn.btn-primary {
            background-color: var(--bs-primary) !important;
            border-color: var(--bs-primary) !important;
            color: #fff !important;
        }

        .dt-buttons .btn.btn-info {
            background-color: var(--bs-info) !important;
            border-color: var(--bs-info) !important;
            color: #fff !important;
        }

        #showModal .nav-pills .nav-link {
            background: #e2e8f0;
            color: #475569;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        #showModal .nav-pills .nav-link:hover {
            background: #cbd5e1;
            color: #1e293b;
        }

        #showModal .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff;
            box-shadow: 0 2px 6px rgba(30, 58, 95, 0.25);
        }

        #showModal .nav-pills .nav-link.active .badge {
            background: rgba(255, 255, 255, 0.25) !important;
            color: #fff !important;
        }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <h2><i class="bi bi-file-text me-2"></i>Hüquqi Aktlar</h2>
        @if($canManage)
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

    {{-- Filters --}}
    <div class="card filter-card mb-3">
        <div class="card-header" data-bs-toggle="collapse" data-bs-target="#filterBody" aria-expanded="true">
            <h5 class="mb-0 d-flex align-items-center"><i class="bi bi-funnel me-2"></i> Filtrlər <i
                    class="bi bi-chevron-down ms-auto"></i></h5>
        </div>
        <div class="collapse show" id="filterBody">
            <div class="card-body filter-row">
                <div class="row g-2">
                    <div class="col-xl-2 col-md-3"><label class="form-label">Sənədin nömrəsi</label><input type="text"
                            id="filter_legal_act_number" class="form-control filter-el" placeholder="Axtar..."></div>
                    <div class="col-xl-2 col-md-3"><label class="form-label">Qısa məzmun</label><input type="text"
                            id="filter_summary" class="form-control filter-el" placeholder="Axtar..."></div>
                    <div class="col-xl-2 col-md-3"><label class="form-label">Sənədin növü</label><select
                            id="filter_act_type" class="form-select filter-select">
                            <option value="">Hamısı</option>@foreach($actTypes as $type)<option value="{{ $type->id }}">
                                {{ $type->name }}
                            </option>@endforeach
                        </select></div>
                    <div class="col-xl-2 col-md-3"><label class="form-label">Kim qəbul edib</label><select
                            id="filter_issued_by" class="form-select filter-select">
                            <option value="">Hamısı</option>@foreach($issuingAuthorities as $a)<option value="{{ $a->id }}">
                                {{ $a->name }}
                            </option>@endforeach
                        </select></div>
                    <div class="col-xl-2 col-md-3"><label class="form-label">İcraçı</label><select id="filter_executor"
                            class="form-select filter-select">
                            <option value="">Hamısı</option>@foreach($executors as $e)<option value="{{ $e->id }}">
                                {{ $e->name }}
                            </option>@endforeach
                        </select></div>
                    <div class="col-xl-2 col-md-3"><label class="form-label">Sənəd tarixi</label><input type="text"
                            id="filter_date_range" class="form-control filter-el" placeholder="Tarix aralığı..." readonly>
                    </div>
                    <div class="col-xl-2 col-md-3"><label class="form-label">İcra müddəti</label><input type="text"
                            id="filter_deadline_range" class="form-control filter-el" placeholder="Tarix aralığı..."
                            readonly></div>
                    <div class="col-xl-2 col-md-3"><label class="form-label">Müddət statusu</label><select
                            id="filter_deadline_status" class="form-select filter-select">
                            <option value="">Hamısı</option>
                            <option value="expired">Müddəti bitib</option>
                            <option value="0day">Son gün</option>
                            <option value="1day">1 gün</option>
                            <option value="2days">2 gün</option>
                            <option value="3days">3 gün</option>
                            <option value="executed">İcra olunub</option>
                        </select></div>
                    <div class="col-xl-2 col-md-3"><label class="form-label">Tapşırıq №</label><input type="text"
                            id="filter_task_number" class="form-control filter-el" placeholder="Axtar..."></div>
                    <div class="col-xl-2 col-md-3"><label class="form-label">Bölmə</label><select id="filter_department"
                            class="form-select filter-select">
                            <option value="">Hamısı</option>@foreach($departments as $d)<option value="{{ $d->id }}">
                                {{ $d->name }}
                            </option>@endforeach
                        </select></div>
                    <div class="col-xl-1 col-md-3 d-flex align-items-end gap-2"><button id="filtersSearchBtn"
                            class="btn btn-primary"><i class="bi bi-search"></i></button><button id="filtersResetBtn"
                            class="btn btn-secondary"><i class="bi bi-x-circle"></i></button></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div style="overflow-x:auto;">
                <table class="table table-hover table-bordered mb-0" id="legalActsTable" style="width:100%">
                    <thead>
                        <tr class="band-header">
                            <th colspan="5" class="bg-band-doc">Sənəd Məlumatları</th>
                            <th colspan="2" class="bg-band-task">Tapşırıq</th>
                            <th colspan="4" class="bg-band-exec">İcraçı Məlumatları</th>
                            <th colspan="3" class="bg-band-icra">İcra Məlumatları</th>
                            <th rowspan="2" class="bg-band-actions" style="position:sticky;right:0;z-index:4;"></th>
                        </tr>
                        <tr class="sub-header">
                            <th class="bg-band-doc-sub">Növü</th>
                            <th class="bg-band-doc-sub">Nömrəsi</th>
                            <th class="bg-band-doc-sub">Tarixi</th>
                            <th class="bg-band-doc-sub">Kim Qəbul Edib</th>
                            <th class="bg-band-doc-sub">Qısa Məzmun</th>
                            <th class="bg-band-task-sub">Tapşırıq №</th>
                            <th class="bg-band-task-sub">Tapşırıq</th>
                            <th class="bg-band-exec-sub">İcraçı</th>
                            <th class="bg-band-exec-sub">Bölmə</th>
                            <th class="bg-band-exec-sub">İcra Müddəti</th>
                            <th class="bg-band-exec-sub">Qeyd</th>
                            <th class="bg-band-icra-sub">Sənəd №</th>
                            <th class="bg-band-icra-sub">Sənəd Tarixi</th>
                            <th class="bg-band-icra-sub">Daxil Edən</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('legal-acts.store') }}" method="POST">@csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Yeni sənəd</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Sənədin nömrəsi <span class="text-danger">*</span></label>
                                <input type="text" name="legal_act_number" class="form-control"
                                    value="{{ old('legal_act_number') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sənədin tarixi <span class="text-danger">*</span></label>
                                <input type="text" name="legal_act_date" class="form-control modal-datepicker"
                                    value="{{ old('legal_act_date') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Növü <span class="text-danger">*</span></label>
                                <select name="act_type_id" class="form-select modal-select2" required>
                                    <option value="">Seç</option>
                                    @foreach($actTypes as $t)
                                        <option value="{{ $t->id }}" {{ old('act_type_id') == $t->id ? 'selected' : '' }}>
                                            {{ $t->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kim qəbul edib <span class="text-danger">*</span></label>
                                <select name="issued_by_id" class="form-select modal-select2" required>
                                    <option value="">Seç</option>
                                    @foreach($issuingAuthorities as $a)
                                        <option value="{{ $a->id }}" {{ old('issued_by_id') == $a->id ? 'selected' : '' }}>
                                            {{ $a->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- MULTIPLE MAIN EXECUTORS --}}
                            <div class="col-12">
                                <label class="form-label">
                                    Əsas icraçı(lar) <span class="text-danger">*</span>
                                    <small class="text-muted fw-normal ms-1">bir və ya bir neçə seçin</small>
                                </label>
                                <select name="main_executor_ids[]" class="form-select modal-select2-multi" multiple
                                    required>
                                    @foreach($executors as $e)
                                        <option value="{{ $e->id }}" {{ in_array($e->id, (array) old('main_executor_ids', [])) ? 'selected' : '' }}>
                                            {{ $e->name }}{{ $e->department ? ' — ' . $e->department->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- MULTIPLE HELPER EXECUTORS --}}
                            <div class="col-12">
                                <label class="form-label">
                                    Digər icraçı(lar)
                                    <small class="text-muted fw-normal ms-1">ixtiyari, bir və ya bir neçə seçin</small>
                                </label>
                                <select name="helper_executor_ids[]" class="form-select modal-select2-multi" multiple>
                                    @foreach($executors as $e)
                                        <option value="{{ $e->id }}" {{ in_array($e->id, (array) old('helper_executor_ids', [])) ? 'selected' : '' }}>
                                            {{ $e->name }}{{ $e->department ? ' — ' . $e->department->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">İcra müddəti</label>
                                <input type="text" name="execution_deadline" class="form-control modal-datepicker"
                                    value="{{ old('execution_deadline') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tapşırıq №</label>
                                <input type="text" name="task_number" class="form-control" value="{{ old('task_number') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Qısa məzmun <span class="text-danger">*</span></label>
                                <textarea name="summary" class="form-control" rows="3"
                                    required>{{ old('summary') }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Tapşırıq</label>
                                <textarea name="task_description" class="form-control"
                                    rows="2">{{ old('task_description') }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Əlaqəli sənəd №</label>
                                <input type="text" name="related_document_number" class="form-control"
                                    value="{{ old('related_document_number') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Əlaqəli sənəd tarixi</label>
                                <input type="text" name="related_document_date" class="form-control modal-datepicker"
                                    value="{{ old('related_document_date') }}">
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

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editForm" method="POST">@csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Redaktə</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="editModalBody">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary"></div>
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

    <div class="modal fade" id="showModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Sənəd məlumatı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="showModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>

    @include('partials.preview-modal')
    <form id="deleteForm" method="POST" style="display:none;">@csrf @method('DELETE')</form>
@endsection

@push('scripts')
    <script src="../../js/mammoth.browser.min.js"></script>
    <script src="{{ asset('js/document-preview.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            var $p = $('.container-fluid');
            ['#filter_act_type', '#filter_issued_by', '#filter_executor', '#filter_deadline_status', '#filter_department'].forEach(function (s) {
                $(s).select2({ theme: 'bootstrap-5', dropdownParent: $p, placeholder: $(s).find('option:first').text(), allowClear: true, width: '100%' });
            });
            var fpCfg = { mode: 'range', dateFormat: 'd.m.Y', locale: flatpickr.l10ns.az, allowInput: false };
            fpCfg.locale.rangeSeparator = ' — ';
            var fpDate = flatpickr('#filter_date_range', Object.assign({}, fpCfg));
            var fpDead = flatpickr('#filter_deadline_range', Object.assign({}, fpCfg));

            function rr(fp) {
                var r = { from: '', to: '' };
                if (fp.selectedDates.length > 0) {
                    var a = fp.selectedDates[0], b = fp.selectedDates.length > 1 ? fp.selectedDates[1] : a;
                    r.from = a.getFullYear() + '-' + String(a.getMonth() + 1).padStart(2, '0') + '-' + String(a.getDate()).padStart(2, '0');
                    r.to = b.getFullYear() + '-' + String(b.getMonth() + 1).padStart(2, '0') + '-' + String(b.getDate()).padStart(2, '0');
                }
                return r;
            }

            function gfp() {
                var p = {}, v;
                if ((v = $('#filter_legal_act_number').val()) && v.trim()) p['col[legal_act_number]'] = v.trim();
                if ((v = $('#filter_summary').val()) && v.trim()) p['col[summary]'] = v.trim();
                if ((v = $('#filter_act_type').val())) p['col[act_type_id]'] = v;
                if ((v = $('#filter_issued_by').val())) p['col[issued_by_id]'] = v;
                if ((v = $('#filter_executor').val())) p['col[executor_id]'] = v;
                if ((v = $('#filter_deadline_status').val())) p['col[deadline_status]'] = v;
                if ((v = $('#filter_task_number').val()) && v.trim()) p['col[task_number]'] = v.trim();
                if ((v = $('#filter_department').val())) p['col[department_id]'] = v;
                var d = rr(fpDate); if (d.from) { p['col[legal_act_date_from]'] = d.from; p['col[legal_act_date_to]'] = d.to; }
                var l = rr(fpDead); if (l.from) { p['col[deadline_from]'] = l.from; p['col[deadline_to]'] = l.to; }
                return p;
            }

            var table = $('#legalActsTable').DataTable({
                processing: true, serverSide: true,
                ajax: {
                    url: "{{ route('legal-acts.load') }}", type: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken },
                    data: function (d) { var f = gfp(); d.col = {}; Object.keys(f).forEach(function (k) { var m = k.match(/^col\[(.+)\]$/); if (m) d.col[m[1]] = f[k]; }); }
                },
                columns: [
                    { data: 'actType', className: 'text-center', render: function (d) { return (!d || d === '-') ? '-' : '<span class="badge" style="background:var(--accent-dark,#1e3a5f)">' + escapeHtml(d) + '</span>'; } },
                    { data: 'legalActNumber', className: 'fw-semibold text-center' },
                    { data: 'legalActDate', className: 'text-center' },
                    { data: 'issuingAuthority' },
                    { data: 'summary', className: 'wrap-cell' },
                    { data: 'taskNumber', className: 'text-center' },
                    { data: 'taskDescription', className: 'wrap-cell' },
                    { data: 'executor' },
                    { data: 'department' },
                    { data: 'deadlineHtml', className: 'text-center' },
                    { data: 'noteHtml' },
                    { data: 'relatedDocNumber', className: 'text-center' },
                    { data: 'relatedDocDate', className: 'text-center' },
                    { data: 'insertedUser' },
                    {
                        data: null, orderable: false, searchable: false, render: function (d) {
                            var h = '<div class="action-btns">';
                            h += '<button class="btn btn-sm btn-info" title="Bax" onclick="showDetails(' + d.id + ')"><i class="bi bi-eye"></i></button>';
                            if (d.hasPendingApproval) h += '<button class="btn btn-sm btn-success" title="Təsdiq gözləyir" onclick="showApproval(' + d.id + ',' + d.pendingLogId + ')"><i class="bi bi-check-circle"></i></button>';
                            if (d.canEdit) h += '<button class="btn btn-sm btn-warning" title="Redaktə" onclick="editRecord(' + d.id + ')"><i class="bi bi-pencil"></i></button>';
                            if (d.canDelete) h += '<button class="btn btn-sm btn-danger" title="Sil" onclick="deleteRecord(' + d.id + ')"><i class="bi bi-trash"></i></button>';
                            return h + '</div>';
                        }
                    }
                ],
                order: [[0, 'desc']], pageLength: 25, lengthMenu: [10, 25, 50, 100], autoWidth: false, orderCellsTop: true,
                dom: '<"d-flex justify-content-between align-items-center flex-wrap px-3 pt-2"lB>rt<"d-flex justify-content-between align-items-center flex-wrap px-3 pb-2"ip>',
                buttons: [
                    { extend: 'colvis', text: '<i class="bi bi-eye me-1"></i> Sütunlar', className: 'btn btn-secondary btn-sm', columns: ':not(:last-child)' },
                    { text: '<i class="bi bi-file-earmark-excel me-1"></i> Excel', className: 'btn btn-primary btn-sm', action: function () { xf('excel'); } },
                    { text: '<i class="bi bi-file-earmark-word me-1"></i> Word', className: 'btn btn-info btn-sm', action: function () { xf('word'); } }
                ],
                language: { paginate: { previous: "&laquo;", next: "&raquo;" }, emptyTable: "Məlumat yoxdur", info: "_START_-_END_ / _TOTAL_", infoEmpty: "Məlumat yoxdur", lengthMenu: "_MENU_ nəticə", processing: "Yüklənir...", zeroRecords: "Tapılmadı" },
                initComplete: function () {
                    var $sd = $('#legalActsTable').closest('[style*="overflow"]');
                    var $w = $('#legalActsTable_wrapper');
                    $sd.before($w.children('.d-flex').first());
                    $sd.after($w.find('.dataTables_info,.dataTables_paginate').closest('.d-flex'));
                }
            });

            $('#filtersSearchBtn').on('click', function () { table.ajax.reload(); });
            $('#filtersResetBtn').on('click', function () {
                $('#filter_legal_act_number,#filter_summary,#filter_task_number').val('');
                $('#filter_act_type,#filter_issued_by,#filter_executor,#filter_deadline_status,#filter_department').val(null).trigger('change');
                fpDate.clear(); fpDead.clear(); table.ajax.reload();
            });
            $p.on('keydown', 'input.filter-el', function (e) { if (e.key === 'Enter') { e.preventDefault(); table.ajax.reload(); } });
            window.xf = function (t) { var p = new URLSearchParams(gfp()); window.location.href = (t === 'excel' ? "{{ route('legal-acts.export.excel') }}" : "{{ route('legal-acts.export.word') }}") + '?' + p.toString(); };

            var $cm = $('#createModal');
            $cm.find('.modal-select2').each(function () {
                $(this).select2({ theme: 'bootstrap-5', dropdownParent: $cm.find('.modal-body'), placeholder: 'Seç', allowClear: true, width: '100%' });
            });
            $cm.find('.modal-select2-multi').each(function () {
                $(this).select2({ theme: 'bootstrap-5', dropdownParent: $cm.find('.modal-body'), placeholder: 'Seçin...', allowClear: true, width: '100%' });
            });
            $cm.find('.modal-datepicker').each(function () {
                flatpickr(this, { dateFormat: 'Y-m-d', locale: flatpickr.l10ns.az, allowInput: true });
            });
            function syncExecutorSelects($modal) {
                var $main = $modal.find('select[name="main_executor_ids[]"]');
                var $helper = $modal.find('select[name="helper_executor_ids[]"]');

                function sync() {
                    var mainVals = ($main.val() || []).map(String);
                    var helperVals = ($helper.val() || []).map(String);

                    $helper.find('option').each(function () {
                        $(this).prop('disabled', mainVals.indexOf(this.value) !== -1);
                    });
                    $main.find('option').each(function () {
                        $(this).prop('disabled', helperVals.indexOf(this.value) !== -1);
                    });

                    $main.trigger('change.select2');
                    $helper.trigger('change.select2');
                }

                $main.on('change', sync);
                $helper.on('change', sync);
                sync();
            }
            function guardExecutorOverlap($modal) {
                $modal.find('form').on('submit', function (e) {
                    var main = ($modal.find('select[name="main_executor_ids[]"]').val() || []).map(String);
                    var helper = ($modal.find('select[name="helper_executor_ids[]"]').val() || []).map(String);
                    var overlap = main.filter(function (v) { return helper.indexOf(v) !== -1; });
                    if (overlap.length > 0) {
                        e.preventDefault();
                        showToast('Eyni icraçı həm əsas həm də digər ola bilməz.', 'danger');
                        return false;
                    }
                });
            }

            guardExecutorOverlap($cm);
            syncExecutorSelects($cm);
        });

        async function showDetails(id) {
            var data = await fetchJson('/legal-acts/' + id); if (!data) return;

            var deptMap = {};
            function groupByDept(list, role) {
                (list || []).forEach(function (e) {
                    var dept = e.department || 'Təyin olunmayıb';
                    if (!deptMap[dept]) deptMap[dept] = { executors: [], key: 'showDept' + Object.keys(deptMap).length };
                    deptMap[dept].executors.push({ name: e.name, position: e.position, role: role });
                });
            }
            groupByDept(data.main_executors, 'Əsas');
            groupByDept(data.helper_executors, 'Digər');

            var logsByUser = {};
            (data.status_logs || []).forEach(function (log) {
                var u = log.user || 'Naməlum';
                if (!logsByUser[u]) logsByUser[u] = [];
                logsByUser[u].push(log);
            });

            var deptKeys = Object.keys(deptMap);
            var rightHtml = '';

            if (deptKeys.length === 0) {
                rightHtml = '<p class="text-muted fst-italic">Hələ status dəyişikliyi yoxdur.</p>';
            } else if (deptKeys.length === 1) {
                rightHtml = buildDeptContent(deptMap[deptKeys[0]].executors, logsByUser);
            } else {
                var tabs = '<ul class="nav nav-pills mb-3" style="gap:4px">';
                var panes = '<div class="tab-content">';
                deptKeys.forEach(function (dept, i) {
                    var d = deptMap[dept];
                    var active = i === 0;
                    tabs += '<li class="nav-item">'
                        + '<a class="nav-link' + (active ? ' active' : '') + '" data-bs-toggle="pill" href="#' + d.key + '" style="font-size:0.78rem;font-weight:600;padding:0.35rem 0.75rem">'
                        + escapeHtml(dept) + ' <span class="badge bg-white text-dark ms-1" style="font-size:0.6rem">' + d.executors.length + '</span>'
                        + '</a></li>';
                    panes += '<div class="tab-pane fade' + (active ? ' show active' : '') + '" id="' + d.key + '">'
                        + buildDeptContent(d.executors, logsByUser)
                        + '</div>';
                });
                tabs += '</ul>';
                panes += '</div>';
                rightHtml = tabs + panes;
            }

            function buildDeptContent(executors, logsByUser) {
                var h = '';
                executors.forEach(function (ex, ei) {
                    if (ei > 0) h += '<hr class="my-2" style="opacity:0.15">';
                    var userLogs = logsByUser[ex.name] || [];
                    var roleBadge = ex.role === 'Əsas'
                        ? '<span class="badge bg-success" style="font-size:0.6rem">Əsas</span>'
                        : '<span class="badge bg-secondary" style="font-size:0.6rem">Digər</span>';

                    h += '<div class="mb-1"><strong style="font-size:0.85rem">' + escapeHtml(ex.name) + '</strong> ' + roleBadge + '</div>';

                    if (userLogs.length === 0) {
                        h += '<p class="text-muted fst-italic" style="font-size:0.78rem;margin-left:1rem">Status yoxdur</p>';
                    } else {
                        h += '<div class="timeline">';
                        userLogs.forEach(function (log) {
                            var accent = '--accent';
                            if (log.approval_status === 'approved') accent = '#16a34a';
                            else if (log.approval_status === 'rejected') accent = '#dc2626';
                            else if (log.approval_status === 'pending') accent = '#ca8a04';
                            else if (log.approval_status === 'partial') accent = '#0284c7';

                            h += '<div class="timeline-item" style="--accent:' + accent + '">'
                                + '<div class="tl-date">' + escapeHtml(log.date || '') + '</div>'
                                + '<div class="tl-user"><i class="bi bi-person me-1"></i>' + escapeHtml(log.user || '') + '</div>'
                                + '<div class="tl-note">' + escapeHtml(log.note || '') + '</div>'
                                + (log.custom_note ? '<div class="tl-custom">"' + escapeHtml(log.custom_note) + '"</div>' : '');

                            if (log.approval_status) {
                                var map = { approved: 'bg-success', pending: 'bg-warning text-dark', rejected: 'bg-danger', partial: 'bg-info text-dark' };
                                var labels = { approved: 'İcra olunub ✓', pending: 'Təsdiq gözləyir', rejected: 'Rədd edilib', partial: 'Natamam' };
                                h += '<div class="mt-1"><span class="badge ' + (map[log.approval_status] || 'bg-secondary') + '">' + (labels[log.approval_status] || log.approval_status) + '</span></div>';
                            }
                            if (log.approved_by) {
                                h += '<div style="font-size:0.75rem" class="text-muted mt-1"><i class="bi bi-check2-circle me-1" style="color:#16a34a"></i>' + escapeHtml(log.approved_by) + ' · ' + escapeHtml(log.approved_at || '') + '</div>';
                            }
                            if (log.approval_note) {
                                h += '<div style="font-size:0.75rem" class="text-muted"><i class="bi bi-chat-left-text me-1"></i>' + escapeHtml(log.approval_note) + '</div>';
                            }
                            h += buildAttachmentHtml(log.attachments);
                            h += '</div>';
                        });
                        h += '</div>';
                    }
                });
                return h;
            }

            document.getElementById('showModalBody').innerHTML =
                '<div class="row">'
                + '<div class="col-lg-7"><h6 class="fw-bold mb-3"><i class="bi bi-file-text me-1"></i> Sənəd</h6>'
                + '<table class="table table-bordered detail-table mb-0">'
                + '<tr><th width="35%">Növ</th><td>' + escapeHtml(data.act_type || '-') + '</td></tr>'
                + '<tr><th>Nömrə</th><td class="fw-bold">' + escapeHtml(data.legal_act_number || '-') + '</td></tr>'
                + '<tr><th>Tarix</th><td>' + escapeHtml(data.legal_act_date || '-') + '</td></tr>'
                + '<tr><th>Kim qəbul edib</th><td>' + escapeHtml(data.issuing_authority || '-') + '</td></tr>'
                + '<tr><th>Qısa məzmun</th><td style="white-space:pre-wrap">' + escapeHtml(data.summary || '-') + '</td></tr>'
                + '<tr><th>Tapşırıq №</th><td>' + escapeHtml(data.task_number || '-') + '</td></tr>'
                + '<tr><th>Tapşırıq</th><td style="white-space:pre-wrap">' + escapeHtml(data.task_description || '-') + '</td></tr>'
                + '<tr><th>İcra müddəti</th><td>' + escapeHtml(data.execution_deadline || '-') + '</td></tr>'
                + '<tr><th>Əlaqəli sənəd</th><td>' + escapeHtml(data.related_document_number || '-') + '</td></tr>'
                + '<tr><th>Daxil edən</th><td>' + escapeHtml(data.inserted_user || '-') + '</td></tr>'
                + '<tr><th>Yaradılma</th><td>' + escapeHtml(data.created_at || '-') + '</td></tr>'
                + '</table></div>'
                + '<div class="col-lg-5"><h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> İcraçılar və Status</h6>' + rightHtml + '</div>'
                + '</div>';

            new bootstrap.Modal(document.getElementById('showModal')).show();
        }

        async function editRecord(id) {
            var data = await fetchJson('/legal-acts/' + id + '/edit'); if (!data) return;
            var $m = $('#editModal');

            function buildOpts(executors, selectedIds) {
                return executors.map(function (e) {
                    var dept = e.department ? ' — ' + e.department.name : '';
                    var sel = selectedIds.indexOf(e.id) !== -1 ? ' selected' : '';
                    return '<option value="' + e.id + '"' + sel + '>' + escapeHtml(e.name + dept) + '</option>';
                }).join('');
            }

            var h = '<div class="row g-3">';
            h += '<div class="col-md-6"><label class="form-label">Nömrə *</label><input type="text" name="legal_act_number" class="form-control" value="' + escapeHtml(data.legal_act_number || '') + '" required></div>';
            h += '<div class="col-md-6"><label class="form-label">Tarix *</label><input type="text" name="legal_act_date" class="form-control edit-datepicker" value="' + escapeHtml(data.legal_act_date || '') + '" required></div>';

            h += '<div class="col-md-6"><label class="form-label">Növ *</label><select name="act_type_id" class="form-select edit-select2" required><option value="">Seç</option>';
            if (data.act_types) data.act_types.forEach(function (t) { h += '<option value="' + t.id + '"' + (t.id == data.act_type_id ? ' selected' : '') + '>' + escapeHtml(t.name) + '</option>'; });
            h += '</select></div>';

            h += '<div class="col-md-6"><label class="form-label">Kim qəbul edib *</label><select name="issued_by_id" class="form-select edit-select2" required><option value="">Seç</option>';
            if (data.authorities) data.authorities.forEach(function (a) { h += '<option value="' + a.id + '"' + (a.id == data.issued_by_id ? ' selected' : '') + '>' + escapeHtml(a.name) + '</option>'; });
            h += '</select></div>';

            h += '<div class="col-12"><label class="form-label">Əsas icraçı(lar) * <small class="text-muted fw-normal">bir və ya bir neçə seçin</small></label>';
            h += '<select name="main_executor_ids[]" class="form-select edit-select2-multi" multiple required>';
            if (data.executors) h += buildOpts(data.executors, data.main_executor_ids || []);
            h += '</select></div>';

            h += '<div class="col-12"><label class="form-label">Digər icraçı(lar) <small class="text-muted fw-normal">ixtiyari</small></label>';
            h += '<select name="helper_executor_ids[]" class="form-select edit-select2-multi" multiple>';
            if (data.executors) h += buildOpts(data.executors, data.helper_executor_ids || []);
            h += '</select></div>';

            h += '<div class="col-md-6"><label class="form-label">İcra müddəti</label><input type="text" name="execution_deadline" class="form-control edit-datepicker" value="' + escapeHtml(data.execution_deadline || '') + '"></div>';
            h += '<div class="col-md-6"><label class="form-label">Tapşırıq №</label><input type="text" name="task_number" class="form-control" value="' + escapeHtml(data.task_number || '') + '"></div>';
            h += '<div class="col-12"><label class="form-label">Qısa məzmun *</label><textarea name="summary" class="form-control" rows="3" required>' + escapeHtml(data.summary || '') + '</textarea></div>';
            h += '<div class="col-12"><label class="form-label">Tapşırıq</label><textarea name="task_description" class="form-control" rows="2">' + escapeHtml(data.task_description || '') + '</textarea></div>';
            h += '<div class="col-md-6"><label class="form-label">Əlaqəli sənəd №</label><input type="text" name="related_document_number" class="form-control" value="' + escapeHtml(data.related_document_number || '') + '"></div>';
            h += '<div class="col-md-6"><label class="form-label">Əlaqəli sənəd tarixi</label><input type="text" name="related_document_date" class="form-control edit-datepicker" value="' + escapeHtml(data.related_document_date || '') + '"></div>';
            h += '</div>';

            document.getElementById('editModalBody').innerHTML = h;
            document.getElementById('editForm').action = '/legal-acts/' + data.id;

            $m.find('.edit-select2').each(function () {
                $(this).select2({ theme: 'bootstrap-5', dropdownParent: $m.find('.modal-body'), placeholder: 'Seç', allowClear: true, width: '100%' });
            });
            $m.find('.edit-select2-multi').each(function () {
                $(this).select2({ theme: 'bootstrap-5', dropdownParent: $m.find('.modal-body'), placeholder: 'Seçin...', allowClear: true, width: '100%' });
            });
            $m.find('.edit-datepicker').each(function () {
                flatpickr(this, { dateFormat: 'Y-m-d', locale: flatpickr.l10ns.az, allowInput: true });
            });

            syncExecutorSelects($m);
            guardExecutorOverlap($m);
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function deleteRecord(id) {
            if (confirm('Silmək istədiyinizə əminsiniz?')) {
                var f = document.getElementById('deleteForm');
                f.action = '/legal-acts/' + id;
                f.submit();
            }
        }
    </script>
@endpush