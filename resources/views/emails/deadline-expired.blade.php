<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tapşırığın İcra Müddəti Başa Çatdı</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background-color: #dc2626; padding: 24px 32px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 20px; }
        .body { padding: 32px; color: #374151; }
        .body p { margin: 0 0 16px; line-height: 1.6; font-size: 15px; }
        .task-info { background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 16px 20px; margin: 24px 0; border-radius: 4px; }
        .task-info p { margin: 6px 0; font-size: 14px; color: #4b5563; }
        .task-info strong { color: #111827; }
        .expired-badge { display: inline-block; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; border-radius: 6px; padding: 6px 14px; font-weight: 700; font-size: 15px; margin: 8px 0; }
        .footer { background-color: #f9fafb; padding: 20px 32px; text-align: center; font-size: 13px; color: #9ca3af; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚨 Tapşırığın İcra Müddəti Başa Çatdı</h1>
        </div>
        <div class="body">
            <p>Hörmətli istifadəçi,</p>
            <p>Sizə təyin olunmuş tapşırığın <strong>icra müddəti başa çatmışdır</strong>. Tapşırığı icra etmədiyiniz halda dərhal sistemə daxil olaraq tədbirlər görün.</p>

            <div class="task-info">
                @if($legalAct->task_number)
                    <p><strong>Qeyd:</strong> {{ $legalAct->task_number }}</p>
                @endif
                @if($legalAct->legal_act_number)
                    <p><strong>Hüquqi akt nömrəsi:</strong> {{ $legalAct->legal_act_number }}</p>
                @endif
                @if($legalAct->execution_deadline)
                    <p><strong>İcra müddəti:</strong></p>
                    <div class="expired-badge">{{ $legalAct->execution_deadline->format('d.m.Y') }} — BAŞA ÇATDI</div>
                @endif
            </div>

            <p>Sistemə daxil olun və tapşırığı icra edin.</p>
        </div>
        <div class="footer">
            <p>Bu məktub {{ config('app.name') }} sistemi tərəfindən avtomatik olaraq göndərilmişdir.</p>
        </div>
    </div>
</body>
</html>
