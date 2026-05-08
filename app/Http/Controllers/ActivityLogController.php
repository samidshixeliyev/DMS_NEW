<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index()
    {
        return view('activity_logs.index');
    }

    public function load(Request $request)
    {
        $draw   = $request->integer('draw');
        $start  = $request->integer('start', 0);
        $length = $request->integer('length', 25);

        $query = ActivityLog::with('user')->latest();

        // ── Filters ────────────────────────────────────────────────────────
        if ($action = $request->input('filter_action')) {
            $query->where('action', $action);
        }

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")
                                                    ->orWhere('surname', 'like', "%{$search}%")
                                                    ->orWhere('username', 'like', "%{$search}%"));
            });
        }

        $total    = ActivityLog::count();
        $filtered = $query->count();

        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(function (ActivityLog $log) {
            $actionLabel = match ($log->action) {
                ActivityLog::ACTION_LOGIN  => '<span class="badge bg-success">Giriş</span>',
                ActivityLog::ACTION_CREATE => '<span class="badge bg-primary">Yaratma</span>',
                ActivityLog::ACTION_UPDATE => '<span class="badge bg-warning text-dark">Yeniləmə</span>',
                ActivityLog::ACTION_DELETE => '<span class="badge bg-danger">Silmə</span>',
                default                    => '<span class="badge bg-secondary">' . e($log->action) . '</span>',
            };

            $user = $log->user;
            $userName = $user
                ? e($user->name . ' ' . $user->surname) . ' <small class="text-muted">(' . e($user->username) . ')</small>'
                : '<span class="text-muted">—</span>';

            return [
                'id'          => $log->id,
                'action'      => $actionLabel,
                'user'        => $userName,
                'description' => e($log->description),
                'ip_address'  => $log->ip_address ?? '—',
                'created_at'  => $log->created_at->format('d.m.Y H:i:s'),
            ];
        });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }
}
