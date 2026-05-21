<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Tapşırıq Təyin Edildi</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #1e3a5f; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p  { margin: 4px 0 0; font-size: 13px; opacity: .8; }
        .body   { padding: 28px 32px; color: #374151; }
        .body p { line-height: 1.6; margin: 0 0 14px; }
        .info-box { background: #f0f4f8; border-left: 4px solid #1e3a5f; border-radius: 4px; padding: 14px 18px; margin: 18px 0; }
        .info-box table { width: 100%; border-collapse: collapse; }
        .info-box td { padding: 5px 0; font-size: 13px; }
        .info-box td:first-child { color: #6b7280; width: 140px; }
        .info-box td:last-child  { color: #111827; font-weight: 600; }
        .btn { display: inline-block; background: #1e3a5f; color: #fff !important; text-decoration: none; padding: 11px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; margin-top: 8px; }
        .footer { background: #f9fafb; padding: 16px 32px; font-size: 11px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📋 Yeni Tapşırıq Təyin Edildi</h1>
        <p>Elektron Xidmətlər Portalı — Avtomatik Bildiriş</p>
    </div>
    <div class="body">
        <p>Hörmətli istifadəçi,</p>
        <p>Sizin bölmənizə yeni tapşırıq təyin edildi. Aşağıda tapşırığın ətraflı məlumatları göstərilib:</p>

        <div class="info-box">
            <table>
                <tr>
                    <td>Akt növü:</td>
                    <td>{{ $legalAct->actType?->name ?? '—' }}</td>
                </tr>
                <tr>
                    <td>Akt nömrəsi:</td>
                    <td>{{ $legalAct->legal_act_number }}</td>
                </tr>
                <tr>
                    <td>Akt tarixi:</td>
                    <td>{{ $legalAct->legal_act_date?->format('d.m.Y') ?? '—' }}</td>
                </tr>
                @if($legalAct->execution_deadline)
                <tr>
                    <td>İcra müddəti:</td>
                    <td>{{ $legalAct->execution_deadline->format('d.m.Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td>Xülasə:</td>
                    <td>{{ \Illuminate\Support\Str::limit($legalAct->summary, 120) }}</td>
                </tr>
                @if($legalAct->task_description)
                <tr>
                    <td>Tapşırıq:</td>
                    <td>{{ \Illuminate\Support\Str::limit($legalAct->task_description, 120) }}</td>
                </tr>
                @endif
            </table>
        </div>

        <p>Tapşırığı görmək üçün sisteminə daxil olun:</p>
        <a href="{{ config('app.url') }}" class="btn">Sistemə Daxil Ol →</a>
    </div>
    <div class="footer">
        Bu e-poçt avtomatik olaraq göndərilmişdir. Cavab verməyin.<br>
        &copy; {{ date('Y') }} Elektron Xidmətlər Portalı
    </div>
</div>
</body>
</html>
