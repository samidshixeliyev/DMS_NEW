<!DOCTYPE html>
<html lang="az">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DMS Tətbiqi')</title>

    <link rel="stylesheet" href=".././fonts.css">

    <link rel="stylesheet" href=".././lib/bs5/bootstrap.min.css">
    <link rel="stylesheet" href=".././lib/bs5/bootstrap-icons.css">
    <link rel="stylesheet" href=".././lib/bs5/buttons.bootstrap5.min.css">

    <link rel="stylesheet" href=".././lib/datatables/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href=".././lib/datatables/fixedColumns.bootstrap5.min.css">

    <link rel="stylesheet" href=".././lib/select2/select2.min.css">
    <link rel="stylesheet" href=".././lib/select2/select2-bootstrap-5-theme.min.css">
    <link rel="stylesheet" href=".././lib/flatpickr/flatpickr.min.css">

    <style>
        :root {
            --primary: #1e3a5f;
            --primary-light: #2a5298;
            --primary-dark: #0f1f33;
            --accent: #00b4d8;
            --accent-light: #48cae4;
            --accent-dark: #0096c7;
            --success: #06d6a0;
            --warning: #ffd166;
            --danger: #ef476f;
            --bg-main: #f0f4f8;
            --bg-card: #ffffff;
            --text-primary: #1a1a2e;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 25px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
            --radius: 12px;
            --radius-sm: 8px;
        }

        * { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif; }
        body { background: var(--bg-main); color: var(--text-primary); margin: 0; overflow: hidden; }
        .card { border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-sm); background: var(--bg-card); overflow: hidden; }
        .card-header { background: var(--bg-card); border-bottom: 1px solid var(--border); padding: 1rem 1.25rem; font-weight: 700; }
        .card-body { padding: 1.25rem; }
        .collapse:not(.show) { display: none !important; }
        .collapse.show { display: block !important; visibility: visible !important; }
        .collapsing { visibility: visible !important; }
        .btn { font-weight: 600; font-size: 0.85rem; border-radius: var(--radius-sm); padding: 0.45rem 1rem; transition: all 0.2s ease; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--primary-light)); border: none; box-shadow: 0 2px 6px rgba(30, 58, 95, 0.25); }
        .btn-primary:hover { background: linear-gradient(135deg, var(--primary-light), var(--primary)); box-shadow: 0 4px 12px rgba(30, 58, 95, 0.35); transform: translateY(-1px); }
        .btn-success { background: linear-gradient(135deg, #059669, var(--success)); border: none; box-shadow: 0 2px 6px rgba(6, 214, 160, 0.25); }
        .btn-success:hover { background: linear-gradient(135deg, var(--success), #059669); transform: translateY(-1px); }
        .btn-info { background: linear-gradient(135deg, var(--accent-dark), var(--accent)); color: #fff; border: none; }
        .btn-info:hover { background: linear-gradient(135deg, var(--accent), var(--accent-dark)); color: #fff; transform: translateY(-1px); }
        .btn-warning { background: linear-gradient(135deg, #e5a100, var(--warning)); color: var(--text-primary); border: none; }
        .btn-warning:hover { transform: translateY(-1px); }
        .btn-danger { background: linear-gradient(135deg, #d63384, var(--danger)); color: #fff; border: none; }
        .btn-danger:hover { transform: translateY(-1px); }
        .btn-secondary { background: #e2e8f0; color: var(--text-primary); border: none; }
        .btn-secondary:hover { background: #cbd5e1; color: var(--text-primary); }
        .btn-sm { padding: 0.3rem 0.6rem; font-size: 0.78rem; }
        .form-control, .form-select { border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 0.875rem; transition: all 0.2s ease; }
        .form-control:focus, .form-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.15); }
        .form-label { font-weight: 600; font-size: 0.825rem; color: var(--text-secondary); margin-bottom: 0.35rem; }
        .modal-content { border: none; border-radius: var(--radius); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); }
        .modal-header { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); color: #fff; border-radius: var(--radius) var(--radius) 0 0; padding: 1rem 1.25rem; }
        .modal-header .modal-title { font-weight: 700; font-size: 1.05rem; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .modal-body { padding: 1.5rem 1.25rem; }
        .modal-footer { border-top: 1px solid var(--border); padding: 0.75rem 1.25rem; }
        .alert { border-radius: var(--radius-sm); border: none; font-weight: 500; font-size: 0.875rem; }
        .alert-success { background: linear-gradient(135deg, rgba(6, 214, 160, 0.1), rgba(6, 214, 160, 0.05)); color: #065f46; border-left: 4px solid var(--success); }
        .alert-danger { background: linear-gradient(135deg, rgba(239, 71, 111, 0.1), rgba(239, 71, 111, 0.05)); color: #9b1c31; border-left: 4px solid var(--danger); }
        .alert-warning { background: linear-gradient(135deg, rgba(255, 209, 102, 0.15), rgba(255, 209, 102, 0.05)); color: #92400e; border-left: 4px solid var(--warning); }
        .alert-info { background: linear-gradient(135deg, rgba(0, 180, 216, 0.1), rgba(0, 180, 216, 0.05)); color: #0c4a6e; border-left: 4px solid var(--accent); }
        .badge { font-weight: 600; font-size: 0.75rem; padding: 0.35em 0.65em; border-radius: 6px; }
        .filter-card .card-header { background: linear-gradient(135deg, rgba(0, 180, 216, 0.08), rgba(0, 180, 216, 0.02)); cursor: pointer; }
        .filter-card .card-header h5 { font-size: 0.95rem; color: var(--primary); }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
        .page-header h2 { font-weight: 800; font-size: 1.75rem; color: var(--primary-dark); margin: 0; letter-spacing: -0.5px; }
        .select2-container--bootstrap-5 .select2-selection { border: 1.5px solid var(--border); border-radius: var(--radius-sm); min-height: 38px; font-size: 0.875rem; }
        .select2-container--bootstrap-5 .select2-selection--single { display: flex !important; align-items: center !important; }
        .select2-selection__rendered { line-height: normal !important; padding-left: 0.75rem; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__placeholder { line-height: normal !important; color: #94a3b8 !important; }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection, .select2-container--bootstrap-5.select2-container--open .select2-selection { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(0, 180, 216, 0.15); }
        .flatpickr-input { background: #fff !important; }
        .detail-table td { font-size: 0.9rem; }
        .empty-state { padding: 3rem 1rem; text-align: center; color: var(--text-secondary); }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }
        @media (max-width: 768px) { .page-header { flex-direction: column; gap: 1rem; align-items: flex-start; } .page-header .btn { width: 100%; } }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--bg-main); }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        #sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); will-change: width; }
        #sidebar.collapsed { width: 80px !important; }
        #sidebar.collapsed .sidebar-text { opacity: 0; width: 0; overflow: hidden; white-space: nowrap; }
        #sidebar.collapsed .sidebar-title-block { display: none; }
        #sidebar.collapsed .user-block { padding: 0.5rem; justify-content: center; }
        #sidebar.collapsed .user-block .user-details { display: none; }
        #sidebar.collapsed .nav-label { display: none; }
        #sidebar.collapsed .nav-link-inner { justify-content: center; padding-left: 0; padding-right: 0; }
        #sidebar.collapsed .nav-link-inner i { margin-right: 0; font-size: 1.15rem; }
        #sidebar.collapsed .sidebar-footer { padding: 0.5rem; }
        #sidebar.collapsed .sidebar-footer .btn-text { display: none; }
        #sidebar.collapsed .sidebar-footer button, #sidebar.collapsed .sidebar-footer a { justify-content: center; padding: 0.5rem; }
        #sidebar.collapsed .collapse-arrow { transform: rotate(180deg); }
        .sidebar-text { transition: opacity 0.25s ease, width 0.25s ease; opacity: 1; }
        #mainContent { transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        #sidebar ::-webkit-scrollbar { width: 4px; }
        #sidebar ::-webkit-scrollbar-track { background: transparent; }
        #sidebar ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); border-radius: 4px; }
        .sidebar-nav-active { background: linear-gradient(135deg, rgba(0, 180, 216, 0.2), rgba(0, 150, 199, 0.1)) !important; border-left: 3px solid #00b4d8 !important; color: #48cae4 !important; }
        .sidebar-nav-active i { color: #48cae4 !important; }
        #sidebar.collapsed .nav-item-wrapper { position: relative; }
        #sidebar.collapsed .nav-item-wrapper:hover .sidebar-tooltip { opacity: 1; visibility: visible; transform: translateX(0) translateY(-50%); }
        .sidebar-tooltip { position: absolute; left: calc(100% + 12px); top: 50%; transform: translateX(-8px) translateY(-50%); background: #1e293b; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.78rem; font-weight: 600; white-space: nowrap; z-index: 9999; opacity: 0; visibility: hidden; transition: all 0.15s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25); pointer-events: none; }
        .sidebar-tooltip::before { content: ''; position: absolute; right: 100%; top: 50%; transform: translateY(-50%); border: 5px solid transparent; border-right-color: #1e293b; }
        #sidebar::before { content: ''; position: absolute; inset: 0; opacity: 0.03; pointer-events: none; background-image: url("../../noise.svg"); }
        #sidebarOverlay { transition: opacity 0.3s ease; }
        @media (max-width: 1024px) { #sidebar { position: fixed !important; z-index: 1050; transform: translateX(-100%); } #sidebar.mobile-open { transform: translateX(0); } #mainContent { margin-left: 0 !important; } }
    </style>

    @stack('styles')
</head>

<body>

    <div style="display:flex; min-height:100vh;">

        <div id="sidebarOverlay"
            style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1040;"
            onclick="toggleMobileSidebar()"></div>

        <aside id="sidebar"
            style="position:fixed; top:0; left:0; height:100vh; width:260px; z-index:1045; display:flex; flex-direction:column; background:linear-gradient(to bottom, #0f1f33, #152a46, #0f1f33); color:#fff; overflow:hidden;">

            <div
                style="display:flex; align-items:center; justify-content:space-between; padding:1rem; border-bottom:1px solid rgba(255,255,255,0.1); position:relative; z-index:1;">
                <a href="{{ url('/') }}" style="display:flex; align-items:center; gap:5px; text-decoration:none;">
                    <div
                        style="width:36px; height:36px; border-radius:12px; background:linear-gradient(135deg,#00b4d8,#0077b6); display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,180,216,0.3); flex-shrink:0;">
                        <i class="bi bi-file-earmark-text" style="color:#fff; font-size:1rem;"></i>
                    </div>
                    <span class="sidebar-text"
                        style="font-weight:800; font-size:1.15rem; letter-spacing:-0.5px; color:#fff;">DMS</span>
                </a>
                <button onclick="toggleSidebar()" class="d-none d-lg-flex"
                    style="width:28px; height:28px; align-items:center; justify-content:center; border-radius:8px; border:none; background:transparent; cursor:pointer;"
                    title="Menyunu yığ/aç">
                    <i class="bi bi-chevron-left collapse-arrow"
                        style="color:rgba(255,255,255,0.6); font-size:0.85rem; transition:transform 0.3s;"></i>
                </button>
            </div>

            <div class="sidebar-title-block"
                style="padding:0.75rem 1rem; border-bottom:1px solid rgba(255,255,255,0.1); position:relative; z-index:1;">
                <p style="font-size:0.68rem; font-weight:600; text-transform:uppercase; letter-spacing:2px; color:rgba(72,202,228,0.7); margin:0; line-height:1.3;">
                    Sənəd İdarəetmə Sistemi</p>
            </div>

            <div class="user-block"
                style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem; border-bottom:1px solid rgba(255,255,255,0.1); position:relative; z-index:1;">
                <div
                    style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#00b4d8,#06d6a0); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.82rem; font-weight:700; box-shadow:0 2px 8px rgba(0,180,216,0.25);">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}{{ mb_substr(auth()->user()->surname, 0, 1) }}
                </div>
                <div class="user-details sidebar-text" style="min-width:0;">
                    <p style="font-size:0.85rem; font-weight:600; color:#fff; margin:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ auth()->user()->name }} {{ auth()->user()->surname }}
                    </p>
                    <span
                        style="display:inline-flex; align-items:center; gap:4px; margin-top:2px; padding:2px 8px; border-radius:999px; font-size:0.62rem; font-weight:700;
                    {{ auth()->user()->user_role === 'admin' ? 'background:rgba(239,71,111,0.2); color:#fb7185;' : (auth()->user()->user_role === 'manager' ? 'background:rgba(0,180,216,0.2); color:#48cae4;' : 'background:rgba(255,255,255,0.1); color:rgba(255,255,255,0.6);') }}">
                        <span style="width:6px; height:6px; border-radius:50%; background:currentColor;"></span>
                        {{ auth()->user()->user_role === 'admin' ? 'Admin' : (auth()->user()->user_role === 'manager' ? 'Menecer' : 'İstifadəçi') }}
                    </span>
                </div>
            </div>

            <nav style="flex:1; padding:0.75rem 0.5rem; position:relative; z-index:1; overflow-x:hidden;">

                <p class="nav-label" style="font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:2px; color:rgba(255,255,255,0.3); padding:4px 12px 8px; margin:0;">
                    Əsas</p>

                <div class="nav-item-wrapper" style="margin-bottom:2px;">
                    <a href="{{ route('legal-acts.index') }}"
                        class="nav-link-inner {{ request()->routeIs('legal-acts.*') ? 'sidebar-nav-active' : '' }}"
                        style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:8px; font-size:0.82rem; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; transition:all 0.2s; border-left:3px solid transparent;">
                        <i class="bi bi-file-text" style="font-size:1rem; width:20px; text-align:center;"></i>
                        <span class="sidebar-text">Hüquqi Aktlar</span>
                    </a>
                    <span class="sidebar-tooltip">Hüquqi Aktlar</span>
                </div>

                @if(auth()->user()->user_role === 'executor' || auth()->user()->canManage())
                    <div class="nav-item-wrapper" style="margin-bottom:2px;">
                        <a href="{{ route('executor.index') }}"
                            class="nav-link-inner {{ request()->routeIs('executor.*') ? 'sidebar-nav-active' : '' }}"
                            style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:8px; font-size:0.82rem; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; transition:all 0.2s; border-left:3px solid transparent;">
                            <i class="bi bi-kanban" style="font-size:1rem; width:20px; text-align:center;"></i>
                            <span class="sidebar-text">İcraçı Paneli</span>
                        </a>
                        <span class="sidebar-tooltip">İcraçı Paneli</span>
                    </div>
                @endif

                @if(auth()->user()->canManage())
                    <div class="nav-item-wrapper" style="margin-bottom:2px;">
                        <a href="{{ route('approvals.index') }}"
                            class="nav-link-inner {{ request()->routeIs('approvals.*') ? 'sidebar-nav-active' : '' }}"
                            style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:8px; font-size:0.82rem; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; transition:all 0.2s; border-left:3px solid transparent;">
                            <i class="bi bi-check2-square" style="font-size:1rem; width:20px; text-align:center;"></i>
                            <span class="sidebar-text">Təsdiq Gözləyənlər
                                @php
                                    $icraNoteIds = \App\Models\ExecutionNote::all()
                                        ->filter(fn($n) => mb_stripos($n->note, 'İcra olunub') !== false || mb_stripos($n->note, 'icra olunub') !== false)
                                        ->pluck('id')
                                        ->toArray();
                                    $pendingCount = count($icraNoteIds) > 0
                                        ? \App\Models\ExecutorStatusLog::where('approval_status', 'pending')
                                            ->whereIn('execution_note_id', $icraNoteIds)
                                            ->count()
                                        : 0;
                                @endphp
                                @if($pendingCount > 0)
                                    <span class="badge bg-danger ms-1" style="font-size:0.65rem;">{{ $pendingCount }}</span>
                                @endif
                            </span>
                        </a>
                        <span class="sidebar-tooltip">Təsdiq Gözləyənlər</span>
                    </div>

                    <div class="nav-item-wrapper" style="margin-bottom:2px;">
                        <a href="{{ route('reports.index') }}"
                            class="nav-link-inner {{ request()->routeIs('reports.*') ? 'sidebar-nav-active' : '' }}"
                            style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:8px; font-size:0.82rem; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; transition:all 0.2s; border-left:3px solid transparent;">
                            <i class="bi bi-bar-chart-line" style="font-size:1rem; width:20px; text-align:center;"></i>
                            <span class="sidebar-text">Hesabat</span>
                        </a>
                        <span class="sidebar-tooltip">Hesabat</span>
                    </div>
                @endif

                <div style="height:1px; background:rgba(255,255,255,0.08); margin:0.5rem 0.75rem;"></div>
                <p class="nav-label" style="font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:2px; color:rgba(255,255,255,0.3); padding:4px 12px 8px; margin:0;">
                    Kataloqlar</p>

                <div class="nav-item-wrapper" style="margin-bottom:2px;">
                    <a href="{{ route('act-types.index') }}"
                        class="nav-link-inner {{ request()->routeIs('act-types.*') ? 'sidebar-nav-active' : '' }}"
                        style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:8px; font-size:0.82rem; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; transition:all 0.2s; border-left:3px solid transparent;">
                        <i class="bi bi-bookmark" style="font-size:1rem; width:20px; text-align:center;"></i>
                        <span class="sidebar-text">Sənəd növləri</span>
                    </a>
                    <span class="sidebar-tooltip">Sənəd növləri</span>
                </div>

                <div class="nav-item-wrapper" style="margin-bottom:2px;">
                    <a href="{{ route('issuing-authorities.index') }}"
                        class="nav-link-inner {{ request()->routeIs('issuing-authorities.*') ? 'sidebar-nav-active' : '' }}"
                        style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:8px; font-size:0.82rem; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; transition:all 0.2s; border-left:3px solid transparent;">
                        <i class="bi bi-building-check" style="font-size:1rem; width:20px; text-align:center;"></i>
                        <span class="sidebar-text">Kim qəbul edib</span>
                    </a>
                    <span class="sidebar-tooltip">Kim qəbul edib</span>
                </div>

                <div class="nav-item-wrapper" style="margin-bottom:2px;">
                    <a href="{{ route('departments.index') }}"
                        class="nav-link-inner {{ request()->routeIs('departments.*') ? 'sidebar-nav-active' : '' }}"
                        style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:8px; font-size:0.82rem; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; transition:all 0.2s; border-left:3px solid transparent;">
                        <i class="bi bi-diagram-3" style="font-size:1rem; width:20px; text-align:center;"></i>
                        <span class="sidebar-text">İdarələr</span>
                    </a>
                    <span class="sidebar-tooltip">İdarələr</span>
                </div>

                <div class="nav-item-wrapper" style="margin-bottom:2px;">
                    <a href="{{ route('executors.index') }}"
                        class="nav-link-inner {{ request()->routeIs('executors.*') ? 'sidebar-nav-active' : '' }}"
                        style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:8px; font-size:0.82rem; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; transition:all 0.2s; border-left:3px solid transparent;">
                        <i class="bi bi-people" style="font-size:1rem; width:20px; text-align:center;"></i>
                        <span class="sidebar-text">İcraçılar</span>
                    </a>
                    <span class="sidebar-tooltip">İcraçılar</span>
                </div>

                <div class="nav-item-wrapper" style="margin-bottom:2px;">
                    <a href="{{ route('execution-notes.index') }}"
                        class="nav-link-inner {{ request()->routeIs('execution-notes.*') ? 'sidebar-nav-active' : '' }}"
                        style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:8px; font-size:0.82rem; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; transition:all 0.2s; border-left:3px solid transparent;">
                        <i class="bi bi-sticky" style="font-size:1rem; width:20px; text-align:center;"></i>
                        <span class="sidebar-text">İcra qeydləri</span>
                    </a>
                    <span class="sidebar-tooltip">İcra qeydləri</span>
                </div>

                @if(auth()->user()->user_role === 'admin')
                    <div style="height:1px; background:rgba(255,255,255,0.08); margin:0.5rem 0.75rem;"></div>
                    <p class="nav-label" style="font-size:0.62rem; font-weight:700; text-transform:uppercase; letter-spacing:2px; color:rgba(255,255,255,0.3); padding:4px 12px 8px; margin:0;">
                        Admin</p>

                    <div class="nav-item-wrapper" style="margin-bottom:2px;">
                        <a href="{{ route('users.index') }}"
                            class="nav-link-inner {{ request()->routeIs('users.*') ? 'sidebar-nav-active' : '' }}"
                            style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:8px; font-size:0.82rem; font-weight:500; color:rgba(255,255,255,0.7); text-decoration:none; transition:all 0.2s; border-left:3px solid transparent;">
                            <i class="bi bi-person-gear" style="font-size:1rem; width:20px; text-align:center;"></i>
                            <span class="sidebar-text">İstifadəçilər</span>
                        </a>
                        <span class="sidebar-tooltip">İstifadəçilər</span>
                    </div>
                @endif

            </nav>

            <div class="sidebar-footer"
                style="border-top:1px solid rgba(255,255,255,0.1); padding:0.75rem; position:relative; z-index:1;">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        style="display:flex; align-items:center; gap:0.5rem; width:100%; padding:0.5rem 0.75rem; border-radius:8px; font-size:0.85rem; font-weight:600; color:#fb7185; background:transparent; border:none; cursor:pointer; transition:all 0.2s;"
                        onmouseover="this.style.background='rgba(239,71,111,0.12)'"
                        onmouseout="this.style.background='transparent'">
                        <i class="bi bi-box-arrow-right" style="font-size:1rem;"></i>
                        <span class="btn-text sidebar-text">Çıxış</span>
                    </button>
                </form>
            </div>
        </aside>

        <div id="mainContent"
            style="flex:1; display:flex; flex-direction:column; height:100vh; overflow:hidden; margin-left:260px;">

            <header
                style="position:sticky; top:0; z-index:1020; background:rgba(255,255,255,0.85); backdrop-filter:blur(12px); border-bottom:1px solid rgba(226,232,240,0.8); padding:0.65rem 1.5rem; display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <button onclick="toggleMobileSidebar()" class="d-lg-none"
                        style="width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:8px; border:none; background:transparent; cursor:pointer;">
                        <i class="bi bi-list" style="font-size:1.25rem; color:#475569;"></i>
                    </button>
                    <div>
                        <h1 style="font-size:1.1rem; font-weight:700; color:#1e293b; margin:0; letter-spacing:-0.3px;">
                            @yield('page-title', 'İdarəetmə paneli')</h1>
                        <p style="font-size:0.72rem; color:#94a3b8; font-weight:500; margin:0;">DMS — Sənəd İdarəetmə Sistemi</p>
                    </div>
                </div>
                <div>
                    <span style="font-size:0.75rem; color:#94a3b8; font-weight:500;">{{ date('d.m.Y') }}</span>
                </div>
            </header>

            <main style="flex:1; overflow:auto; padding:1.25rem;">
                <div class="container-fluid" style="max-width:100%; padding:0;">
                    @yield('content')
                </div>
            </main>

            <footer style="border-top:1px solid #e2e8f0; background:#fff; padding:0.6rem 1.5rem; text-align:center;">
                <p style="font-size:0.75rem; font-weight:500; color:#94a3b8; margin:0;">&copy; {{ date('Y') }} DMS &mdash; Sənəd İdarəetmə Sistemi</p>
            </footer>
        </div>
    </div>

    <script src="../../js/jquery-3.7.1.min.js"></script>
    <script src="../../lib/bs5/bootstrap.bundle.min.js"></script>

    <script src="../../lib/datatables/jquery.dataTables.min.js"></script>
    <script src="../../lib/datatables/dataTables.bootstrap5.min.js"></script>
    <script src="../../lib/datatables/dataTables.buttons.min.js"></script>
    <script src="../../lib/datatables/dataTables.fixedColumns.min.js"></script>
    <script src="../../lib/bs5/buttons.bootstrap5.min.js"></script>
    <script src="../../lib/datatables/buttons.colVis.min.js"></script>

    <script src="../../lib/select2/select2.min.js"></script>
    <script src="../../lib/flatpickr/flatpickr.min.js"></script>
    <script src="../../lib/flatpickr/flatpickr_az.js"></script>

    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const EXPANDED_W = '260px';
        const COLLAPSED_W = '72px';

        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            sidebar.classList.add('collapsed');
            sidebar.style.width = COLLAPSED_W;
            mainContent.style.marginLeft = COLLAPSED_W;
        }

        function toggleSidebar() {
            const isCollapsed = sidebar.classList.toggle('collapsed');
            sidebar.style.width = isCollapsed ? COLLAPSED_W : EXPANDED_W;
            mainContent.style.marginLeft = isCollapsed ? COLLAPSED_W : EXPANDED_W;
            localStorage.setItem('sidebar-collapsed', isCollapsed);
        }

        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleMobileSidebar() {
            const isOpen = sidebar.classList.toggle('mobile-open');
            sidebarOverlay.style.display = isOpen ? 'block' : 'none';
        }

        document.querySelectorAll('.nav-link-inner:not(.sidebar-nav-active)').forEach(function (el) {
            el.addEventListener('mouseenter', function () { this.style.color = '#fff'; this.style.background = 'rgba(255,255,255,0.08)'; });
            el.addEventListener('mouseleave', function () { this.style.color = 'rgba(255,255,255,0.7)'; this.style.background = 'transparent'; });
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const userRole = @json(auth()->user()?->user_role ?? 'user');
        const canManage = ['admin', 'manager'].includes(userRole);
        const isAdmin = userRole === 'admin';

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(text));
            return div.innerHTML;
        }

        async function fetchJson(url) {
            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                });
                if (!response.ok) throw new Error('Network response was not ok');
                return await response.json();
            } catch (error) {
                console.error('Fetch error:', error);
                alert('Məlumat yüklənmədi. Yenidən cəhd edin.');
                return null;
            }
        }

        document.querySelectorAll('.alert-dismissible').forEach(function (alertEl) {
            setTimeout(function () {
                var bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                bsAlert.close();
            }, 4000);
        });

        $.fn.select2.defaults.set('theme', 'bootstrap-5');
        $.fn.select2.defaults.set('language', {
            noResults: function () { return 'Nəticə tapılmadı'; },
            searching: function () { return 'Axtarılır...'; },
            removeAllItems: function () { return 'Hamısını sil'; }
        });
    </script>

    @stack('scripts')
</body>

</html>