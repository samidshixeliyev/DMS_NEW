@extends('layouts.app')

@section('title', 'Hesabat')

@push('styles')
<style>
    .stat-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.9rem 1rem;
        transition: box-shadow 0.2s ease;
    }
    .stat-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    }
    .stat-card .stat-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .stat-card .stat-value {
        font-size: 1.35rem;
        font-weight: 800;
        line-height: 1;
        color: #1e293b;
    }
    .stat-card .stat-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #94a3b8;
        margin-top: 2px;
    }

    .report-table thead th {
        background: #1e3a5f;
        color: #fff;
        font-size: 0.74rem;
        font-weight: 600;
        text-align: center;
        padding: 0.55rem 0.45rem;
        white-space: nowrap;
        border: 1px solid rgba(255,255,255,0.12);
    }
    .report-table tbody td {
        font-size: 0.8rem;
        text-align: center;
        padding: 0.5rem 0.45rem;
        vertical-align: middle;
    }
    .report-table tbody tr:hover {
        background-color: #f8fafc !important;
    }
    .report-table tfoot td {
        font-size: 0.8rem;
        padding: 0.55rem 0.45rem;
    }

    .progress-thin {
        height: 6px;
        border-radius: 3px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .progress-thin .bar {
        height: 100%;
        border-radius: 3px;
        transition: width 0.6s ease;
    }

    .chart-container {
        position: relative;
        height: 280px;
    }

    .loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0.85);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: 12px;
    }

    .num-badge {
        display: inline-block;
        min-width: 26px;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 700;
        text-align: center;
        line-height: 1.3;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <h2><i class="bi bi-bar-chart-line me-2"></i>Hesabat</h2>
    <button class="btn btn-primary btn-sm" id="btnExport">
        <i class="bi bi-file-earmark-excel me-1"></i> Excel
    </button>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-auto"><strong style="font-size:0.82rem;"><i class="bi bi-funnel me-1"></i> Filtr:</strong></div>
            <div class="col-md-3">
                <select id="filterDepartment" class="form-select form-select-sm">
                    <option value="">Bütün idarələr</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary" id="btnRefresh"><i class="bi bi-arrow-clockwise"></i></button>
            </div>
        </div>
    </div>
</div>

<div class="row g-2 mb-3" id="summaryCards">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="stat-dot" style="background:#1e3a5f;"></div>
                <div class="stat-label">Cəmi</div>
            </div>
            <div class="stat-value" id="statTotal">-</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="stat-dot" style="background:#059669;"></div>
                <div class="stat-label">İcra olunub</div>
            </div>
            <div class="stat-value" id="statExecuted" style="color:#059669;">-</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="stat-dot" style="background:#d97706;"></div>
                <div class="stat-label">Təsdiq gözləyir</div>
            </div>
            <div class="stat-value" id="statPending" style="color:#d97706;">-</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="stat-dot" style="background:#dc2626;"></div>
                <div class="stat-label">İmtina edilib</div>
            </div>
            <div class="stat-value" id="statRejected" style="color:#dc2626;">-</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="stat-dot" style="background:#0284c7;"></div>
                <div class="stat-label">İcradadır</div>
            </div>
            <div class="stat-value" id="statInProgress" style="color:#0284c7;">-</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-2 mb-1">
                <div class="stat-dot" style="background:#7c3aed;"></div>
                <div class="stat-label">Müddəti keçib</div>
            </div>
            <div class="stat-value" id="statOverdue" style="color:#7c3aed;">-</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card" style="position:relative;">
            <div class="card-header py-2"><h6 class="mb-0" style="font-size:0.85rem;"><i class="bi bi-bar-chart me-1"></i> İcraçılar üzrə</h6></div>
            <div class="card-body py-2">
                <div class="chart-container"><canvas id="barChart"></canvas></div>
            </div>
            <div class="loading-overlay" id="barLoading"><div class="spinner-border text-primary" style="width:2rem;height:2rem;"></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card" style="position:relative;">
            <div class="card-header py-2"><h6 class="mb-0" style="font-size:0.85rem;"><i class="bi bi-pie-chart me-1"></i> Ümumi bölgü</h6></div>
            <div class="card-body py-2">
                <div class="chart-container"><canvas id="pieChart"></canvas></div>
            </div>
            <div class="loading-overlay" id="pieLoading"><div class="spinner-border text-primary" style="width:2rem;height:2rem;"></div></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0" style="font-size:0.85rem;"><i class="bi bi-table me-1"></i> Detallı statistika</h6>
        <small class="text-muted" id="tableInfo" style="font-size:0.72rem;"></small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0 report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>İcraçı</th>
                        <th>İdarə</th>
                        <th>Vəzifə</th>
                        <th>Cəmi</th>
                        <th>İcra olunub</th>
                        <th>Təsdiq gözləyir</th>
                        <th>İmtina edilib</th>
                        <th>İcradadır</th>
                        <th>Başlanmayıb</th>
                        <th>Müddəti keçib</th>
                        <th>İcra faizi</th>
                    </tr>
                </thead>
                <tbody id="reportBody">
                    <tr><td colspan="12" class="text-center py-4"><div class="spinner-border text-primary" style="width:1.5rem;height:1.5rem;"></div></td></tr>
                </tbody>
                <tfoot id="reportFoot"></tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="../../lib/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var barChart = null;
    var pieChart = null;

    $('#filterDepartment').select2({ theme: 'bootstrap-5', placeholder: 'Bütün idarələr', allowClear: true, width: '100%' });

    function loadReport() {
        var dept = $('#filterDepartment').val();
        var params = dept ? '?department_id=' + dept : '';

        fetch('/reports/load' + params, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) { renderReport(data); })
        .catch(function() { alert('Hesabat yüklənmədi.'); });
    }

    function nb(val, color) {
        if (val === 0) return '<span class="num-badge" style="background:#f1f5f9;color:#94a3b8;">0</span>';
        return '<span class="num-badge" style="background:' + color + '15;color:' + color + ';">' + val + '</span>';
    }

    function renderReport(data) {
        var stats = data.stats;
        var totals = data.totals;

        document.getElementById('statTotal').textContent = totals.total;
        document.getElementById('statExecuted').textContent = totals.executed;
        document.getElementById('statPending').textContent = totals.pending;
        document.getElementById('statRejected').textContent = totals.rejected;
        document.getElementById('statInProgress').textContent = totals.in_progress;
        document.getElementById('statOverdue').textContent = totals.overdue;

        var tbody = '';
        stats.forEach(function(s, i) {
            var rateColor = s.execution_rate >= 80 ? '#059669' : (s.execution_rate >= 50 ? '#d97706' : (s.total > 0 ? '#dc2626' : '#94a3b8'));

            tbody += '<tr>'
                + '<td>' + (i + 1) + '</td>'
                + '<td class="text-start fw-semibold">' + escapeHtml(s.executor_name) + '</td>'
                + '<td class="text-start">' + escapeHtml(s.department) + '</td>'
                + '<td class="text-start" style="font-size:0.75rem;color:#64748b;">' + escapeHtml(s.position) + '</td>'
                + '<td><strong>' + s.total + '</strong></td>'
                + '<td>' + nb(s.executed, '#059669') + '</td>'
                + '<td>' + nb(s.pending, '#d97706') + '</td>'
                + '<td>' + nb(s.rejected, '#dc2626') + '</td>'
                + '<td>' + nb(s.in_progress, '#0284c7') + '</td>'
                + '<td>' + nb(s.not_started, '#6b7280') + '</td>'
                + '<td>' + nb(s.overdue, '#7c3aed') + '</td>'
                + '<td>'
                + '<div class="d-flex align-items-center gap-2">'
                + '<div class="progress-thin flex-grow-1"><div class="bar" style="width:' + s.execution_rate + '%;background:' + rateColor + '"></div></div>'
                + '<span style="color:' + rateColor + ';font-size:0.75rem;font-weight:700;min-width:36px;">' + s.execution_rate + '%</span>'
                + '</div></td>'
                + '</tr>';
        });
        document.getElementById('reportBody').innerHTML = tbody || '<tr><td colspan="12" class="text-center py-4 text-muted">Məlumat yoxdur</td></tr>';

        var tRateColor = totals.execution_rate >= 80 ? '#059669' : (totals.execution_rate >= 50 ? '#d97706' : '#dc2626');
        document.getElementById('reportFoot').innerHTML =
            '<tr style="background:#f1f5f9;">'
            + '<td colspan="4" class="text-end fw-bold" style="font-size:0.78rem;">CƏMİ</td>'
            + '<td><strong>' + totals.total + '</strong></td>'
            + '<td>' + nb(totals.executed, '#059669') + '</td>'
            + '<td>' + nb(totals.pending, '#d97706') + '</td>'
            + '<td>' + nb(totals.rejected, '#dc2626') + '</td>'
            + '<td>' + nb(totals.in_progress, '#0284c7') + '</td>'
            + '<td>' + nb(totals.not_started, '#6b7280') + '</td>'
            + '<td>' + nb(totals.overdue, '#7c3aed') + '</td>'
            + '<td><strong style="color:' + tRateColor + ';font-size:0.8rem;">' + totals.execution_rate + '%</strong></td>'
            + '</tr>';

        document.getElementById('tableInfo').textContent = stats.length + ' icraçı · ' + totals.total + ' sənəd';

        renderBarChart(stats);
        renderPieChart(totals);
    }

    function renderBarChart(stats) {
        document.getElementById('barLoading').style.display = 'none';
        var filtered = stats.filter(function(s) { return s.total > 0; });
        filtered.sort(function(a, b) { return b.total - a.total; });
        if (filtered.length > 15) filtered = filtered.slice(0, 15);

        var labels = filtered.map(function(s) { return s.executor_name.length > 18 ? s.executor_name.substring(0, 18) + '…' : s.executor_name; });

        var config = {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'İcra olunub', data: filtered.map(function(s) { return s.executed; }), backgroundColor: '#059669', borderRadius: 3 },
                    { label: 'Təsdiq gözləyir', data: filtered.map(function(s) { return s.pending; }), backgroundColor: '#d97706', borderRadius: 3 },
                    { label: 'İmtina edilib', data: filtered.map(function(s) { return s.rejected; }), backgroundColor: '#dc2626', borderRadius: 3 },
                    { label: 'İcradadır', data: filtered.map(function(s) { return s.in_progress; }), backgroundColor: '#0284c7', borderRadius: 3 },
                    { label: 'Başlanmayıb', data: filtered.map(function(s) { return s.not_started; }), backgroundColor: '#cbd5e1', borderRadius: 3 },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'rectRounded', font: { size: 10, weight: '500' }, padding: 12 } } },
                scales: {
                    x: { stacked: true, ticks: { font: { size: 9 }, maxRotation: 45 }, grid: { display: false } },
                    y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } }, grid: { color: '#f1f5f9' } }
                }
            }
        };

        if (barChart) barChart.destroy();
        barChart = new Chart(document.getElementById('barChart'), config);
    }

    function renderPieChart(totals) {
        document.getElementById('pieLoading').style.display = 'none';
        var values = [totals.executed, totals.pending, totals.rejected, totals.in_progress, totals.not_started];
        var labels = ['İcra olunub', 'Təsdiq gözləyir', 'İmtina edilib', 'İcradadır', 'Başlanmayıb'];
        var colors = ['#059669', '#d97706', '#dc2626', '#0284c7', '#cbd5e1'];

        var config = {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: values, backgroundColor: colors, borderWidth: 2, borderColor: '#fff', hoverOffset: 6 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', font: { size: 10, weight: '500' }, padding: 12 } }
                }
            }
        };

        if (pieChart) pieChart.destroy();
        pieChart = new Chart(document.getElementById('pieChart'), config);
    }

    document.getElementById('btnRefresh').addEventListener('click', loadReport);
    $('#filterDepartment').on('change', loadReport);

    document.getElementById('btnExport').addEventListener('click', function() {
        var dept = $('#filterDepartment').val();
        var params = dept ? '?department_id=' + dept : '';
        window.location.href = '/reports/export-excel' + params;
    });

    loadReport();
});
</script>
@endpush