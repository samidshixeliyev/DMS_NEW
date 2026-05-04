<?php

namespace App\Http\Controllers;

use App\Models\Executor;
use App\Models\LegalAct;
use App\Models\ExecutorStatusLog;
use App\Models\ExecutionNote;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function getIcraOlunubNoteIds(): array
    {
        return ExecutionNote::active()
            ->where(fn($q) => $q->where('note', 'like', '%İcra olunub%')->orWhere('note', 'like', '%icra olunub%'))
            ->pluck('id')
            ->toArray();
    }

    public function index()
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            $departments = Department::active()->get();
        } elseif ($user->canManage() && ($assignDeptId = $user->canAssignDeptId())) {
            $deptIds     = Department::descendantIdsOf($assignDeptId);
            $departments = Department::active()->whereIn('id', $deptIds)->get();
        } else {
            $departments = collect();
        }
        return view('reports.index', compact('departments'));
    }

    public function load(Request $request)
    {
        $user      = auth()->user();
        $icraIds   = $this->getIcraOlunubNoteIds();
        $icraIdSet = array_flip($icraIds);

        $emptyTotals = ['total' => 0, 'executed' => 0, 'pending' => 0, 'rejected' => 0,
            'in_progress' => 0, 'not_started' => 0, 'overdue' => 0, 'execution_rate' => 0];

        // Determine the scope: admin sees all, managers see only their assignable dept subtree
        $assignDeptId = null;
        if (!$user->isAdmin()) {
            $assignDeptId = $user->canAssignDeptId();
            if (!$assignDeptId) {
                return response()->json(['stats' => [], 'totals' => $emptyTotals]);
            }
        }

        // Query 1: executors scoped to the manager's dept subtree
        $executorsQuery = Executor::with('department')->active();
        if ($assignDeptId) {
            $scopeDeptIds = Department::descendantIdsOf($assignDeptId);
            $executorsQuery->whereIn('department_id', $scopeDeptIds);
        }

        $executors = $executorsQuery
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->input('department_id')))
            ->orderBy('name')
            ->get();

        if ($executors->isEmpty()) {
            return response()->json(['stats' => [], 'totals' => $emptyTotals]);
        }

        $executorIds = $executors->pluck('id')->toArray();

        // Query 2: all pivot rows (executor → act) in one shot
        $pivotByExecutor = DB::table('legal_act_executor')
            ->whereIn('executor_id', $executorIds)
            ->select('executor_id', 'legal_act_id')
            ->get()
            ->groupBy('executor_id')
            ->map(fn($rows) => $rows->pluck('legal_act_id')->toArray());

        $allActIds = collect($pivotByExecutor->values())->flatten()->unique()->values()->toArray();

        if (empty($allActIds)) {
            $stats = $executors->map(fn($e) => $this->emptyStats($e))->values()->toArray();
            return response()->json(['stats' => $stats, 'totals' => $this->computeTotals($stats)]);
        }

        // Query 3: active acts with their deadlines — scoped to the manager's own organization
        $actsQuery = LegalAct::whereIn('id', $allActIds)->where('is_deleted', false)->select('id', 'execution_deadline');
        if ($assignDeptId) {
            $actsQuery->where('organization_id', $assignDeptId);
        }
        $actsById = $actsQuery->get()->keyBy('id');

        // Query 4: user IDs grouped by executor_id
        $usersByExecutor = \App\Models\User::whereIn('executor_id', $executorIds)
            ->select('id', 'executor_id')
            ->get()
            ->groupBy('executor_id')
            ->map(fn($users) => $users->pluck('id')->toArray());

        $allUserIds = collect($usersByExecutor->values())->flatten()->unique()->values()->toArray();

        // Query 5+6: latest status log per (user_id, legal_act_id) combo
        $activeActIds = $actsById->keys()->toArray();
        $latestLogIds = empty($allUserIds) ? collect() : DB::table('executor_status_logs')
            ->whereIn('legal_act_id', $activeActIds)
            ->whereIn('user_id', $allUserIds)
            ->groupBy('legal_act_id', 'user_id')
            ->selectRaw('MAX(id) as id')
            ->pluck('id');

        $logsByUserId = $latestLogIds->isEmpty() ? collect() : ExecutorStatusLog::whereIn('id', $latestLogIds)
            ->select('id', 'legal_act_id', 'user_id', 'execution_note_id', 'approval_status')
            ->get()
            ->groupBy('user_id');

        $today = now()->startOfDay();
        $stats = [];

        foreach ($executors as $executor) {
            $execActIds   = array_filter($pivotByExecutor->get($executor->id, []), fn($id) => $actsById->has($id));
            $totalAssigned = count($execActIds);

            if ($totalAssigned === 0) {
                $stats[] = $this->emptyStats($executor);
                continue;
            }

            $execActIdSet  = array_flip($execActIds);
            $execUserIds   = $usersByExecutor->get($executor->id, []);

            // Build latest-log-per-act for this executor (in-memory, no extra queries)
            $latestLogPerAct = [];
            foreach ($execUserIds as $userId) {
                foreach ($logsByUserId->get($userId, collect()) as $log) {
                    if (isset($execActIdSet[$log->legal_act_id])) {
                        if (!isset($latestLogPerAct[$log->legal_act_id]) || $log->id > $latestLogPerAct[$log->legal_act_id]->id) {
                            $latestLogPerAct[$log->legal_act_id] = $log;
                        }
                    }
                }
            }

            $executed = $pending = $rejected = $inProgress = $notStarted = $overdue = 0;

            foreach ($execActIds as $actId) {
                $log    = $latestLogPerAct[$actId] ?? null;
                $isIcra = $log && isset($icraIdSet[$log->execution_note_id]);

                if (!$log) {
                    $notStarted++;
                } elseif ($isIcra && $log->approval_status === 'approved') {
                    $executed++;
                } elseif ($isIcra && in_array($log->approval_status, ['pending', 'partial'])) {
                    $pending++;
                } elseif ($isIcra && $log->approval_status === 'rejected') {
                    $rejected++;
                } else {
                    $inProgress++;
                }

                if (!($isIcra && $log?->approval_status === 'approved')) {
                    $act = $actsById->get($actId);
                    if ($act?->execution_deadline && $act->execution_deadline->lt($today)) {
                        $overdue++;
                    }
                }
            }

            $onTime = max(0, $totalAssigned - $overdue - $executed);

            $stats[] = [
                'executor_id'    => $executor->id,
                'executor_name'  => $executor->name,
                'department'     => $executor->department?->name ?? '-',
                'position'       => $executor->position ?? '-',
                'total'          => $totalAssigned,
                'executed'       => $executed,
                'pending'        => $pending,
                'rejected'       => $rejected,
                'in_progress'    => $inProgress,
                'not_started'    => $notStarted,
                'overdue'        => $overdue,
                'on_time'        => $onTime,
                'execution_rate' => round(($executed / $totalAssigned) * 100, 1),
            ];
        }

        return response()->json([
            'stats'  => $stats,
            'totals' => $this->computeTotals($stats),
        ]);
    }

    private function emptyStats(Executor $executor): array
    {
        return [
            'executor_id'    => $executor->id,
            'executor_name'  => $executor->name,
            'department'     => $executor->department?->name ?? '-',
            'position'       => $executor->position ?? '-',
            'total'          => 0, 'executed' => 0, 'pending' => 0,
            'rejected'       => 0, 'in_progress' => 0, 'not_started' => 0,
            'overdue'        => 0, 'on_time' => 0, 'execution_rate' => 0,
        ];
    }

    private function computeTotals(array $stats): array
    {
        $keys   = ['total', 'executed', 'pending', 'rejected', 'in_progress', 'not_started', 'overdue'];
        $totals = array_combine($keys, array_map(fn($k) => array_sum(array_column($stats, $k)), $keys));
        $totals['execution_rate'] = $totals['total'] > 0
            ? round(($totals['executed'] / $totals['total']) * 100, 1) : 0;
        return $totals;
    }

    public function exportExcel(Request $request)
    {
        $data = $this->load($request)->getData(true);
        $stats = $data['stats'];
        $totals = $data['totals'];

        $rows = '';
        $i = 1;
        foreach ($stats as $s) {
            $rateColor = $s['execution_rate'] >= 80 ? '#d1fae5' : ($s['execution_rate'] >= 50 ? '#fef9c3' : ($s['total'] > 0 ? '#fee2e2' : ''));
            $rows .= '<tr' . ($rateColor ? ' style="background-color:' . $rateColor . '"' : '') . '>'
                . '<td>' . $i++ . '</td>'
                . '<td>' . e($s['executor_name']) . '</td>'
                . '<td>' . e($s['department']) . '</td>'
                . '<td>' . e($s['position']) . '</td>'
                . '<td>' . $s['total'] . '</td>'
                . '<td>' . $s['executed'] . '</td>'
                . '<td>' . $s['pending'] . '</td>'
                . '<td>' . $s['rejected'] . '</td>'
                . '<td>' . $s['in_progress'] . '</td>'
                . '<td>' . $s['not_started'] . '</td>'
                . '<td>' . $s['overdue'] . '</td>'
                . '<td>' . $s['execution_rate'] . '%</td>'
                . '</tr>';
        }

        $date = now()->format('d.m.Y H:i');
        $html = <<<HTML
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Hesabat</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
    <style>
        td, th { border: 1px solid #333; padding: 4px 8px; font-family: Arial; font-size: 10pt; vertical-align: top; text-align: center; }
        th { background-color: #1e3a5f; color: #ffffff; font-weight: bold; }
    </style>
</head>
<body>
    <table>
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
        <tbody>{$rows}</tbody>
        <tfoot>
            <tr style="background-color:#e2e8f0;font-weight:bold;">
                <td colspan="4">CƏMİ</td>
                <td>{$totals['total']}</td>
                <td>{$totals['executed']}</td>
                <td>{$totals['pending']}</td>
                <td>{$totals['rejected']}</td>
                <td>{$totals['in_progress']}</td>
                <td>{$totals['not_started']}</td>
                <td>{$totals['overdue']}</td>
                <td>{$totals['execution_rate']}%</td>
            </tr>
        </tfoot>
    </table>
    <br>
    <table><tr><td>Hazırlanma tarixi: {$date}</td></tr></table>
</body>
</html>
HTML;

        $filename = 'executor_report_' . now()->format('Y_m_d_His') . '.xls';
        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }
}