<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifrəni Dəyişdir — DMS</title>
    <link rel="stylesheet" href="{{ asset('lib/bs5/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('lib/bs5/bootstrap-icons.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            width: 420px;
        }
        .card-header {
            background: #1e3a5f;
            color: #fff;
            border-radius: 14px 14px 0 0 !important;
            padding: 1.5rem;
            text-align: center;
        }
        .card-header i { font-size: 2rem; display: block; margin-bottom: .5rem; }
        .card-body { padding: 2rem; }
        .notice {
            background: #fff8e1;
            border-left: 4px solid #f59e0b;
            border-radius: 6px;
            padding: .75rem 1rem;
            font-size: .85rem;
            margin-bottom: 1.5rem;
            color: #78350f;
        }
        .btn-primary { background: #1e3a5f; border-color: #1e3a5f; }
        .btn-primary:hover { background: #2a5298; border-color: #2a5298; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <i class="bi bi-shield-lock-fill"></i>
        <h5 class="mb-0">Şifrənizi Dəyişdirin</h5>
    </div>
    <div class="card-body">
        <div class="notice">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            Hesabınıza ilk dəfə daxil olursunuz. Davam etmək üçün şifrənizi dəyişdirməlisiniz.
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-sm py-2 px-3 mb-3" style="font-size:.84rem">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('force-password-change.submit') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Yeni şifrə</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Ən azı 6 simvol" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Şifrəni təkrarla</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Yenidən daxil edin" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="bi bi-check-circle me-1"></i>Şifrəni Yadda Saxla
            </button>
        </form>

        <div class="text-center mt-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-link btn-sm text-muted">
                    <i class="bi bi-box-arrow-left me-1"></i>Çıxış et
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
