@extends('layouts.app')

@section('title', 'İcraçı Paneli')

@push('styles')
    <style>
        .row-pending td {
            background-color: #fef3cd !important;
        }

        .row-pending:hover td {
            background-color: #ffeaa7 !important;
        }

        .row-pending td:first-child {
            box-shadow: inset 3px 0 0 #f59e0b;
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

        #executorTable tbody td {
            font-size: 0.82rem;
            padding: 0.5rem 0.65rem;
            vertical-align: middle;
            text-align: center;
        }

        #executorTable tbody td.wrap-cell {
            white-space: normal;
            word-break: break-word;
            text-align: left;
            min-width: 180px;
            max-width: 280px;
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
            margin-bottom: 1.5rem;
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

        .timeline-item .tl-attachment {
            font-size: 0.78rem;
            margin-top: 6px;
        }

        /* File list preview */
        .file-list {
            list-style: none;
            padding: 0;
            margin: 0.5rem 0 0;
        }

        .file-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.4rem 0.6rem;
            margin-bottom: 0.3rem;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 0.8rem;
        }

        .file-list li .file-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 260px;
        }

        .file-list li .file-remove {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1rem;
            padding: 0 0.25rem;
            line-height: 1;
        }

        .file-list li .file-remove:hover {
            color: #dc2626;
        }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <h2><i class="bi bi-kanban me-2"></i>İcraçı Paneli</h2>
        @if(auth()->user()->executor)
            <span class="badge bg-primary" style="font-size:0.85rem;"><i
                    class="bi bi-person-badge me-1"></i>{{ auth()->user()->executor->name }}</span>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i>
    {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button"
                class="btn-close" data-bs-dismiss="alert"></button>
    </div>@endif

    @if(auth()->user()->canManage() && $allExecutors->isNotEmpty())
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto"><strong><i class="bi bi-person-lines-fill me-1"></i> İcraçı seçin:</strong></div>
                    <div class="col-md-4">
                        <select id="viewAsExecutor" class="form-select">
                            <option value="">Hamısı (ümumi baxış)</option>
                            @foreach($allExecutors as $ex)
                                <option value="{{ $ex->id }}">
                                    {{ $ex->name }}{{ $ex->department ? ' — ' . $ex->department->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div style="overflow-x:auto;">
                <table class="table table-hover table-bordered mb-0" id="executorTable" style="width:100%">
                    <thead>
                        <tr>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">#</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Növü</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Nömrəsi</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Tarixi</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Kim Qəbul Edib</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Qısa Məzmun</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Tapşırıq №</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">İcra Müddəti</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Status</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Rolum</th>
                            <th style="background:#374151;color:#fff;text-align:center;">Əməliyyat</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Show Detail Modal --}}
    <div class="modal fade" id="showModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>Sənəd məlumatı</h5><button type="button"
                        class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="showModalBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Bağla</button></div>
            </div>
        </div>
    </div>

    {{-- Status Change Modal --}}
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="statusForm" method="POST" enctype="multipart/form-data">@csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Status dəyiş</h5><button
                            type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Standart qeyd <span class="text-danger">*</span></label>
                            <select name="execution_note_id" id="status_note" class="form-select" required>
                                <option value="">Seç</option>
                                @foreach($executionNotes as $n)
                                    <option value="{{ $n->id }}">{{ $n->note }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sərbəst qeyd</label>
                            <textarea name="custom_note" class="form-control" rows="3"
                                placeholder="Əlavə qeydinizi yazın..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sübut sənədləri <small class="text-muted">(Word, PDF, JPG, PNG — maks.
                                    10MB, 10 fayl)</small></label>
                            <input type="file" name="attachments[]" id="attachmentsInput" class="form-control"
                                accept=".doc,.docx,.pdf,.jpg,.jpeg,.png" multiple>
                            <div class="form-text text-warning" id="attachmentWarning" style="display:none;">
                                <i class="bi bi-exclamation-triangle me-1"></i> "İcra olunub" statusunda Admin tərəfindən sübut sənədi tələb olunarsa, sənəd əlavə etmək məcburidir.
                            </div>
                            <ul class="file-list" id="fileList"></ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İmtina</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Təsdiqlə</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Preview Modal (shared partial) --}}
    @include('partials.preview-modal')

    <div id="uploadOverlay"
        style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;justify-content:center;align-items:center;">
        <div
            style="background:#fff;border-radius:12px;padding:2rem 3rem;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.3);">
            <div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem;"></div>
            <div class="fw-bold" style="font-size:1.1rem;">Yüklənir...</div>
            <div class="text-muted" style="font-size:0.85rem;">Zəhmət olmasa gözləyin</div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="../../js/mammoth.browser.min.js"></script>
    <script src="{{ asset('js/document-preview.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            $('#viewAsExecutor').select2({ theme: 'bootstrap-5', placeholder: 'İcraçı seçin...', allowClear: true, width: '100%' });
            $('#viewAsExecutor').on('change', function () { table.ajax.reload(); });
            // ─── DataTable ──────────────────────────────────────────────
            var table = $('#executorTable').DataTable({
                processing: true, serverSide: true,
                ajax: { url: "{{ route('executor.load') }}", type: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken }, data: function (d) { var ex = $('#viewAsExecutor').val(); if (ex) d.view_as_executor_id = ex; } },
                columns: [
                    { data: 'rowNum', className: 'text-center', orderable: false },
                    { data: 'actType', className: 'text-center', render: function (d) { return (!d || d === '-') ? '-' : '<span class="badge" style="background:var(--accent-dark,#1e3a5f)">' + escapeHtml(d) + '</span>'; } },
                    { data: 'legalActNumber', className: 'fw-semibold text-center' },
                    { data: 'legalActDate', className: 'text-center' },
                    { data: 'issuingAuthority' },
                    { data: 'summary', className: 'wrap-cell' },
                    { data: 'taskNumber', className: 'text-center' },
                    { data: 'deadlineHtml', className: 'text-center' },
                    { data: 'statusHtml', className: 'text-center' },
                    { data: 'roleHtml', className: 'text-center' },
                    {
                        data: null, orderable: false, searchable: false, render: function (d) {
                            var btns = '<div class="action-btns">';
                            btns += '<button class="btn btn-sm btn-info" title="Bax" onclick="showDetails(' + d.id + ')"><i class="bi bi-eye"></i></button>';
                            if (d.canChangeStatus) {
                                btns += '<button class="btn btn-sm btn-warning" title="Status" onclick="changeStatus(' + d.id + ')"><i class="bi bi-pencil-square"></i></button>';
                            } else {
                                btns += '<button class="btn btn-sm btn-secondary" disabled title="Dəyişiklik mümkün deyil"><i class="bi bi-lock"></i></button>';
                            }
                            return btns + '</div>';
                        }
                    }
                ],
                order: [[3, 'desc']], pageLength: 25, lengthMenu: [10, 25, 50, 100],
                dom: '<"d-flex justify-content-between align-items-center flex-wrap px-3 pt-2"l>rt<"d-flex justify-content-between align-items-center flex-wrap px-3 pb-2"ip>',
                language: {
                    paginate: {
                        previous: "&laquo;",
                        next: "&raquo;"
                    },
                    emptyTable: "{{ auth()->user()->canManage() ? 'İcraçı seçin' : 'Sizə təyin olunmuş sənəd yoxdur' }}",
                    info: "_START_-_END_ / _TOTAL_",
                    infoEmpty: "Məlumat yoxdur",
                    lengthMenu: "_MENU_ nəticə",
                    processing: "Yüklənir...",
                    zeroRecords: "Tapılmadı"
                }
            });

            // ─── Attachment warning toggle ──────────────────────────────
            $('#status_note').on('change', function () {
                document.getElementById('attachmentWarning').style.display =
                    $(this).find('option:selected').text().toLowerCase().indexOf('icra olunub') !== -1 ? 'block' : 'none';
            });

            // ─── Multi-file list preview with remove ────────────────────
            var attachmentsInput = document.getElementById('attachmentsInput');
            var fileListEl = document.getElementById('fileList');
            var selectedFiles = new DataTransfer();

            attachmentsInput.addEventListener('change', function () {
                // Append new files to existing selection
                for (var i = 0; i < this.files.length; i++) {
                    if (selectedFiles.files.length >= 10) {
                        alert('Maksimum 10 fayl yükləyə bilərsiniz.');
                        break;
                    }
                    selectedFiles.items.add(this.files[i]);
                }
                this.files = selectedFiles.files;
                renderFileList();
            });

            function renderFileList() {
                fileListEl.innerHTML = '';
                for (var i = 0; i < selectedFiles.files.length; i++) {
                    var file = selectedFiles.files[i];
                    var li = document.createElement('li');

                    var nameSpan = document.createElement('span');
                    nameSpan.className = 'file-name';
                    nameSpan.title = file.name;

                    var icon = getFileIcon(file.name);
                    nameSpan.innerHTML = icon + ' ' + escapeHtml(file.name) + ' <small class="text-muted">(' + formatFileSize(file.size) + ')</small>';

                    var removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.className = 'file-remove';
                    removeBtn.innerHTML = '<i class="bi bi-x-circle"></i>';
                    removeBtn.dataset.index = i;
                    removeBtn.addEventListener('click', function () {
                        removeFile(parseInt(this.dataset.index));
                    });

                    li.appendChild(nameSpan);
                    li.appendChild(removeBtn);
                    fileListEl.appendChild(li);
                }
            }

            function removeFile(index) {
                var newDt = new DataTransfer();
                for (var i = 0; i < selectedFiles.files.length; i++) {
                    if (i !== index) newDt.items.add(selectedFiles.files[i]);
                }
                selectedFiles = newDt;
                attachmentsInput.files = selectedFiles.files;
                renderFileList();
            }

            function getFileIcon(filename) {
                var ext = filename.split('.').pop().toLowerCase();
                if (ext === 'pdf') return '<i class="bi bi-file-earmark-pdf text-danger"></i>';
                if (ext === 'doc' || ext === 'docx') return '<i class="bi bi-file-earmark-word text-primary"></i>';
                if (ext === 'jpg' || ext === 'jpeg' || ext === 'png') return '<i class="bi bi-file-earmark-image text-success"></i>';
                return '<i class="bi bi-file-earmark"></i>';
            }

            function formatFileSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / 1048576).toFixed(1) + ' MB';
            }

            // Reset file list when modal is closed
            document.getElementById('statusModal').addEventListener('hidden.bs.modal', function () {
                selectedFiles = new DataTransfer();
                attachmentsInput.files = selectedFiles.files;
                fileListEl.innerHTML = '';
            });

            document.getElementById('statusForm').addEventListener('submit', function () {
                document.getElementById('uploadOverlay').style.display = 'flex';
            });

            // Make selectedFiles accessible for changeStatus reset
            window._resetStatusFiles = function () {
                selectedFiles = new DataTransfer();
                attachmentsInput.files = selectedFiles.files;
                fileListEl.innerHTML = '';
            };
        });

        // ─── Show details ───────────────────────────────────────────────
        async function showDetails(id) {
            var data = await fetchJson('/executor/legal-acts/' + id); if (!data) return;
            var logsHtml = '<p class="text-muted fst-italic">Hələ status dəyişikliyi yoxdur.</p>';
            if (data.status_logs && data.status_logs.length > 0) {
                logsHtml = '<div class="timeline">';
                data.status_logs.forEach(function (log) {
                    var accent = '#3b82f6';
                    if (log.approval_status === 'approved') accent = '#16a34a';
                    else if (log.approval_status === 'rejected') accent = '#dc2626';
                    else if (log.approval_status === 'pending') accent = '#ca8a04';
                    else if (log.approval_status === 'partial') accent = '#0284c7';
                    logsHtml += '<div class="timeline-item" style="--accent:' + accent + '">'
                        + '<div class="tl-date">' + escapeHtml(log.date || '') + '</div>'
                        + '<div class="tl-user"><i class="bi bi-person me-1"></i>' + escapeHtml(log.user || '') + '</div>'
                        + '<div class="tl-note">' + escapeHtml(log.note || '') + '</div>'
                        + (log.custom_note ? '<div class="tl-custom">"' + escapeHtml(log.custom_note) + '"</div>' : '');
                    if (log.approval_status) {
                        var map = { approved: 'bg-success', pending: 'bg-warning text-dark', rejected: 'bg-danger', partial: 'bg-info text-dark' };
                        var labels = { approved: 'İcra olunub ✓', pending: 'Təsdiq gözləyir', rejected: 'İmtina edilib', partial: 'Natamam' };
                        logsHtml += '<div class="mt-1"><span class="badge ' + (map[log.approval_status] || 'bg-secondary') + '">' + (labels[log.approval_status] || log.approval_status) + '</span></div>';
                    }
                    if (log.approved_by) {
                        logsHtml += '<div style="font-size:0.75rem" class="text-muted mt-1"><i class="bi bi-check2-circle me-1" style="color:#16a34a"></i>' + escapeHtml(log.approved_by) + ' · ' + escapeHtml(log.approved_at || '') + '</div>';
                    }
                    if (log.approval_note) {
                        logsHtml += '<div style="font-size:0.75rem" class="text-muted"><i class="bi bi-chat-left-text me-1"></i>' + escapeHtml(log.approval_note) + '</div>';
                    }
                    logsHtml += buildAttachmentHtml(log.attachments)
                        + '</div>';
                });
            }
            document.getElementById('showModalBody').innerHTML =
                '<div class="row">'
                + '<div class="col-lg-7">'
                + '<h6 class="fw-bold mb-3"><i class="bi bi-file-text me-1"></i> Sənəd</h6>'
                + '<table class="table table-bordered detail-table mb-0">'
                + '<tr><th width="35%">Növ</th><td>' + escapeHtml(data.act_type || '-') + '</td></tr>'
                + '<tr><th>Nömrə</th><td class="fw-bold">' + escapeHtml(data.legal_act_number || '-') + '</td></tr>'
                + '<tr><th>Tarix</th><td>' + escapeHtml(data.legal_act_date || '-') + '</td></tr>'
                + '<tr><th>Qısa məzmun</th><td style="white-space:pre-wrap">' + escapeHtml(data.summary || '-') + '</td></tr>'
                + '<tr><th>Kim qəbul edib</th><td>' + escapeHtml(data.issuing_authority || '-') + '</td></tr>'
                + '<tr><th>Əsas icraçı</th><td>' + escapeHtml(data.main_executor || '-') + (data.main_executor_department ? ' <small>(' + escapeHtml(data.main_executor_department) + ')</small>' : '') + '</td></tr>'
                + '<tr><th>Digər icraçı</th><td>' + escapeHtml(data.helper_executor || '-') + (data.helper_executor_department ? ' <small>(' + escapeHtml(data.helper_executor_department) + ')</small>' : '') + '</td></tr>'
                + '<tr><th>Tapşırıq №</th><td>' + escapeHtml(data.task_number || '-') + '</td></tr>'
                + '<tr><th>Tapşırıq</th><td style="white-space:pre-wrap">' + escapeHtml(data.task_description || '-') + '</td></tr>'
                + '<tr><th>İcra müddəti</th><td>' + escapeHtml(data.execution_deadline || '-') + '</td></tr>'
                + '</table></div>'
                + '<div class="col-lg-5">'
                + '<h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Status Tarixçəsi</h6>'
                + logsHtml
                + '</div></div>';
            new bootstrap.Modal(document.getElementById('showModal')).show();
        }

        // ─── Change status ──────────────────────────────────────────────
        function changeStatus(id) {
            document.getElementById('statusForm').action = '/executor/legal-acts/' + id + '/status';
            document.getElementById('status_note').value = '';
            document.getElementById('attachmentWarning').style.display = 'none';
            document.querySelector('#statusForm textarea[name="custom_note"]').value = '';
            if (window._resetStatusFiles) window._resetStatusFiles();
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }
    </script>
@endpush