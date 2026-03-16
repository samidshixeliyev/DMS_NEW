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
        return ExecutionNote::all()
            ->filter(fn($n) => mb_stripos($n->note, 'İcra olunub') !== false || mb_stripos($n->note, 'icra olunub') !== false)
            ->pluck('id')->toArray();
    }

    public function index()
    {
        $departments = Department::active()->get();
        return view('reports.index', compact('departments'));
    }

    public function load(Request $request)
    {
        $icraIds = $this->getIcraOlunubNoteIds();

        $query = Executor::with('department')->active();

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        $executors = $query->orderBy('name')->get();

        $stats = [];

        foreach ($executors as $executor) {
            $actIds = DB::table('legal_act_executor')
                ->where('executor_id', $executor->id)
                ->pluck('legal_act_id')
                ->toArray();

            $activeActIds = LegalAct::whereIn('id', $actIds)
                ->where('is_deleted', false)
                ->pluck('id')
                ->toArray();

            $totalAssigned = count($activeActIds);

            if ($totalAssigned === 0) {
                $stats[] = [
                    'executor_id' => $executor->id,
                    'executor_name' => $executor->name,
                    'department' => $executor->department?->name ?? '-',
                    'position' => $executor->position ?? '-',
                    'total' => 0,
                    'executed' => 0,
                    'pending' => 0,
                    'rejected' => 0,
                    'in_progress' => 0,
                    'not_started' => 0,
                    'overdue' => 0,
                    'on_time' => 0,
                    'execution_rate' => 0,
                ];
                continue;
            }

            $userIds = \App\Models\User::where('executor_id', $executor->id)->pluck('id')->toArray();

            $latestLogs = [];
            if (!empty($userIds)) {
                $allLogs = ExecutorStatusLog::whereIn('legal_act_id', $activeActIds)
                    ->whereIn('user_id', $userIds)
                    ->with('executionNote')
                    ->orderByDesc('id')
                    ->get();

                foreach ($allLogs as $log) {
                    if (!isset($latestLogs[$log->legal_act_id])) {
                        $latestLogs[$log->legal_act_id] = $log;
                    }
                }
            }

            $executed = 0;
            $pending = 0;
            $rejected = 0;
            $inProgress = 0;
            $notStarted = 0;
            $overdue = 0;

            $actsWithDeadline = LegalAct::whereIn('id', $activeActIds)
                ->whereNotNull('execution_deadline')
                ->get()
                ->keyBy('id');

            foreach ($activeActIds as $actId) {
                $log = $latestLogs[$actId] ?? null;

                if (!$log) {
                    $notStarted++;
                } else {
                    $isIcra = !empty($icraIds) && in_array($log->execution_note_id, $icraIds);

                    if ($isIcra && $log->approval_status === 'approved') {
                        $executed++;
                    } elseif ($isIcra && in_array($log->approval_status, ['pending', 'partial'])) {
                        $pending++;
                    } elseif ($isIcra && $log->approval_status === 'rejected') {
                        $rejected++;
                    } else {
                        $inProgress++;
                    }
                }

                if (!($log && !empty($icraIds) && in_array($log->execution_note_id, $icraIds) && $log->approval_status === 'approved')) {
                    $act = $actsWithDeadline[$actId] ?? null;
                    if ($act && $act->execution_deadline && $act->execution_deadline->lt(now()->startOfDay())) {
                        $overdue++;
                    }
                }
            }

            $onTime = $totalAssigned - $overdue - $executed;
            if ($onTime < 0) $onTime = 0;

            $stats[] = [
                'executor_id' => $executor->id,
                'executor_name' => $executor->name,
                'department' => $executor->department?->name ?? '-',
                'position' => $executor->position ?? '-',
                'total' => $totalAssigned,
                'executed' => $executed,
                'pending' => $pending,
                'rejected' => $rejected,
                'in_progress' => $inProgress,
                'not_started' => $notStarted,
                'overdue' => $overdue,
                'on_time' => $onTime,
                'execution_rate' => $totalAssigned > 0 ? round(($executed / $totalAssigned) * 100, 1) : 0,
            ];
        }

        $totals = [
            'total' => array_sum(array_column($stats, 'total')),
            'executed' => array_sum(array_column($stats, 'executed')),
            'pending' => array_sum(array_column($stats, 'pending')),
            'rejected' => array_sum(array_column($stats, 'rejected')),
            'in_progress' => array_sum(array_column($stats, 'in_progress')),
            'not_started' => array_sum(array_column($stats, 'not_started')),
            'overdue' => array_sum(array_column($stats, 'overdue')),
        ];
        $totals['execution_rate'] = $totals['total'] > 0 ? round(($totals['executed'] / $totals['total']) * 100, 1) : 0;

        return response()->json([
            'stats' => $stats,
            'totals' => $totals,
        ]);
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
                <th>Rədd edilib</th>
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