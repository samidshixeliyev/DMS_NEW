@extends('layouts.app')

@section('title', 'Aktivlik Jurnalı')

@section('content')
    <div class="page-header">
        <h2><i class="bi bi-journal-text me-2"></i>Aktivlik Jurnalı</h2>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 fw-semibold" style="font-size:0.82rem;">Əməliyyat:</label>
                </div>
                <div class="col-auto">
                    <select id="filterAction" class="form-select form-select-sm" style="min-width:160px;">
                        <option value="">Hamısı</option>
                        <option value="login">Giriş</option>
                        <option value="create">Yaratma</option>
                        <option value="update">Yeniləmə</option>
                        <option value="delete">Silmə</option>
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <button class="btn btn-sm btn-outline-secondary" id="btnRefresh">
                        <i class="bi bi-arrow-clockwise me-1"></i> Yenilə
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div style="overflow-x:auto;">
                <table id="logsTable" class="table table-hover table-sm mb-0" style="width:100%;">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:110px;">Əməliyyat</th>
                            <th>İstifadəçi</th>
                            <th>Təsvir</th>
                            <th style="width:130px;">IP ünvan</th>
                            <th style="width:155px;">Tarix / Saat</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var table = $('#logsTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route('activity-logs.load') }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: function (d) {
                d.filter_action = $('#filterAction').val();
            }
        },
        columns: [
            { data: 'action',      orderable: false },
            { data: 'user',        orderable: false },
            { data: 'description', orderable: false },
            { data: 'ip_address',  orderable: false },
            { data: 'created_at',  orderable: false },
        ],
        pageLength: 25,
        language: {
            url: '/lib/datatables/az.json',
            processing: '<div class="spinner-border spinner-border-sm text-primary" role="status"></div> Yüklənir...'
        },
        order: [],
        dom: '<"d-flex align-items-center justify-content-between mb-2"lf>rtip',
    });

    $('#filterAction').on('change', function () { table.ajax.reload(); });
    $('#btnRefresh').on('click',    function () { table.ajax.reload(); });
})();
</script>
@endpush
