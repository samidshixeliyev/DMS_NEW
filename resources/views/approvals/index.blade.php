@extends('layouts.app')

@section('title', 'Təsdiq Gözləyənlər')

@push('styles')
    <style>
        .row-pending td {
            background-color: #fefce8 !important;
        }

        .row-pending:hover td {
            background-color: #fef9c3 !important;
        }

        .row-pending td:first-child {
            box-shadow: inset 3px 0 0 #ca8a04;
        }

        #approvalsTable tbody td {
            font-size: 0.82rem;
            padding: 0.5rem 0.65rem;
            vertical-align: middle;
            text-align: center;
        }

        #approvalsTable tbody td.wrap-cell {
            white-space: normal;
            word-break: break-word;
            text-align: left;
            min-width: 150px;
            max-width: 250px;
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

        .approval-badge-pending {
            background: #fbbf24;
            color: #92400e;
        }

        .approval-badge-approved {
            background: #34d399;
            color: #065f46;
        }

        .approval-badge-rejected {
            background: #fb7185;
            color: #9f1239;
        }
    </style>
@endpush

@section('content')
    <div class="page-header">
        <h2><i class="bi bi-check2-square me-2"></i>Təsdiq Gözləyənlər</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button"
                class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div style="overflow-x:auto;">
                <table class="table table-hover table-bordered mb-0" id="approvalsTable" style="width:100%">
                    <thead>
                        <tr>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">#</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Növü</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Nömrəsi</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Tarixi</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Qısa Məzmun</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">İcraçı</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Göndərən</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Göndərilmə Tarixi</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">Sənədlər</th>
                            <th style="background:#1e3a5f;color:#fff;text-align:center;">İcra Müddəti</th>
                            <th style="background:#374151;color:#fff;text-align:center;">Əməliyyat</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Detail / Review Modal --}}
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-clipboard-check me-2"></i>İcra Qeydini Nəzərdən Keçir</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reviewModalBody"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Approve Confirmation Modal --}}
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="approveForm" method="POST">
                    @csrf
                    <div class="modal-header" style="background: linear-gradient(135deg, #059669, #06d6a0); color: #fff;">
                        <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>İcranı Təsdiqlə</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Bu sənədin icrasını təsdiqləmək istəyirsiniz?</p>
                        <div class="mb-3">
                            <label class="form-label">Qeyd (ixtiyari)</label>
                            <textarea name="approval_note" class="form-control" rows="3"
                                placeholder="Təsdiq qeydini yazın..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İmtina</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i>
                            Təsdiqlə</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Confirmation Modal --}}
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="rejectForm" method="POST">
                    @csrf
                    <div class="modal-header" style="background: linear-gradient(135deg, #d63384, #ef476f); color: #fff;">
                        <h5 class="modal-title"><i class="bi bi-x-circle me-2"></i>İcranı Rədd Et</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-danger fw-semibold">Bu sənədin icrasını rədd edirsiniz. İcraçı yenidən status təyin
                            edəcək.</p>
                        <div class="mb-3">
                            <label class="form-label">Rədd səbəbi <span class="text-danger">*</span></label>
                            <textarea name="approval_note" class="form-control" rows="3"
                                placeholder="Rədd səbəbini mütləq yazın..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İmtina</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle me-1"></i> Rədd Et</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Preview Modal --}}
    @include('partials.preview-modal')
@endsection

@push('scripts')
    <script src="../../js/mammoth.browser.min.js"></script>
    <script src="{{ asset('js/document-preview.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var table = $('#approvalsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('approvals.load') }}",
                    type: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    data: function (d) {

                    }
                },
                columns: [
                    { data: 'rowNum', className: 'text-center', orderable: false },
                    {
                        data: 'actType', className: 'text-center', render: function (d) {
                            return (!d || d === '-') ? '-' : '<span class="badge" style="background:var(--accent-dark,#1e3a5f)">' + escapeHtml(d) + '</span>';
                        }
                    },
                    { data: 'legalActNumber', className: 'fw-semibold text-center' },
                    { data: 'legalActDate', className: 'text-center' },
                    { data: 'summary', className: 'wrap-cell' },
                    { data: 'executor' },
                    { data: 'submittedBy' },
                    { data: 'submittedAt', className: 'text-center' },
                    {
                        data: 'attachmentCount', className: 'text-center', render: function (d) {
                            return d > 0
                                ? '<span class="badge bg-primary">' + d + ' fayl</span>'
                                : '<span class="badge bg-secondary">Yoxdur</span>';
                        }
                    },
                    { data: 'deadlineHtml', className: 'text-center' },
                    {
                        data: null, orderable: false, searchable: false, render: function (d) {
                            return '<div class="action-btns">'
                                + '<button class="btn btn-sm btn-info" title="Bax" onclick="reviewDetails(' + d.id + ')"><i class="bi bi-eye"></i></button>'
                                + '<button class="btn btn-sm btn-success" title="Təsdiqlə" onclick="approveRecord(' + d.logId + ')"><i class="bi bi-check-lg"></i></button>'
                                + '<button class="btn btn-sm btn-danger" title="Rədd et" onclick="rejectRecord(' + d.logId + ')"><i class="bi bi-x-lg"></i></button>'
                                + '</div>';
                        }
                    }
                ],
                order: [[7, 'desc']],
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                dom: '<"d-flex justify-content-between align-items-center flex-wrap px-3 pt-2"l>rt<"d-flex justify-content-between align-items-center flex-wrap px-3 pb-2"ip>',
                language: {
                    paginate: { previous: "&laquo;", next: "&raquo;" },
                    emptyTable: "Təsdiq gözləyən sənəd yoxdur",
                    info: "_START_-_END_ / _TOTAL_",
                    infoEmpty: "Məlumat yoxdur",
                    lengthMenu: "_MENU_ nəticə",
                    processing: "Yüklənir...",
                    zeroRecords: "Tapılmadı"
                }
            });
        });

        async function reviewDetails(id) {
            var data = await fetchJson('/approvals/show/' + id);
            if (!data) return;

            var logsHtml = '<p class="text-muted fst-italic">Status tarixçəsi yoxdur.</p>';
            if (data.status_logs && data.status_logs.length > 0) {
                logsHtml = '<div class="timeline">';
                data.status_logs.forEach(function (log) {
                    var approvalBadge = '';
                    if (log.approval_status === 'pending') {
                        approvalBadge = '<span class="badge approval-badge-pending ms-1">Təsdiq gözləyir</span>';
                    } else if (log.approval_status === 'approved') {
                        approvalBadge = '<span class="badge approval-badge-approved ms-1">Təsdiqlənib ✓</span>';
                    } else if (log.approval_status === 'rejected') {
                        approvalBadge = '<span class="badge approval-badge-rejected ms-1">Rədd edilib ✗</span>';
                    }

                    logsHtml += '<div class="timeline-item">'
                        + '<div class="tl-date">' + escapeHtml(log.date || '') + '</div>'
                        + '<div class="tl-user"><i class="bi bi-person me-1"></i>' + escapeHtml(log.user || '') + '</div>'
                        + '<div class="tl-note">' + escapeHtml(log.note || '') + approvalBadge + '</div>'
                        + (log.custom_note ? '<div class="tl-custom">"' + escapeHtml(log.custom_note) + '"</div>' : '')
                        + (log.approval_note ? '<div class="mt-1"><small class="text-' + (log.approval_status === 'rejected' ? 'danger' : 'success') + '"><i class="bi bi-chat-dots me-1"></i>' + escapeHtml(log.approval_note) + '</small></div>' : '')
                        + (log.approved_by ? '<div><small class="text-muted"><i class="bi bi-person-check me-1"></i>' + escapeHtml(log.approved_by) + ' — ' + escapeHtml(log.approved_at || '') + '</small></div>' : '')
                        + buildAttachmentHtml(log.attachments)
                        + '</div>';
                });
                logsHtml += '</div>';
            }

            document.getElementById('reviewModalBody').innerHTML =
                '<div class="row">'
                + '<div class="col-lg-6">'
                + '<h6 class="fw-bold mb-3"><i class="bi bi-file-text me-1"></i> Sənəd</h6>'
                + '<table class="table table-bordered detail-table mb-0">'
                + '<tr><th width="35%">Növ</th><td>' + escapeHtml(data.act_type || '-') + '</td></tr>'
                + '<tr><th>Nömrə</th><td class="fw-bold">' + escapeHtml(data.legal_act_number || '-') + '</td></tr>'
                + '<tr><th>Tarix</th><td>' + escapeHtml(data.legal_act_date || '-') + '</td></tr>'
                + '<tr><th>Qısa məzmun</th><td style="white-space:pre-wrap">' + escapeHtml(data.summary || '-') + '</td></tr>'
                + '<tr><th>Kim qəbul edib</th><td>' + escapeHtml(data.issuing_authority || '-') + '</td></tr>'
                + '<tr><th>Əsas icraçı</th><td>' + escapeHtml(data.main_executor || '-') + (data.main_executor_department ? ' <small>(' + escapeHtml(data.main_executor_department) + ')</small>' : '') + '</td></tr>'
                + '<tr><th>Digər icraçı</th><td>' + escapeHtml(data.helper_executor || '-') + '</td></tr>'
                + '<tr><th>İcra müddəti</th><td>' + escapeHtml(data.execution_deadline || '-') + '</td></tr>'
                + '</table></div>'
                + '<div class="col-lg-6">'
                + '<h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-1"></i> Status Tarixçəsi</h6>'
                + logsHtml
                + '</div></div>';

            new bootstrap.Modal(document.getElementById('reviewModal')).show();
        }

        function approveRecord(logId) {
            document.getElementById('approveForm').action = '/approvals/' + logId + '/approve';
            document.querySelector('#approveForm textarea[name="approval_note"]').value = '';
            new bootstrap.Modal(document.getElementById('approveModal')).show();
        }

        function rejectRecord(logId) {
            document.getElementById('rejectForm').action = '/approvals/' + logId + '/reject';
            document.querySelector('#rejectForm textarea[name="approval_note"]').value = '';
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }
    </script>
@endpush