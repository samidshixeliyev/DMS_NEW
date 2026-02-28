<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DMS — Giriş</title>

    
    <link href="../../fonts.css" rel="stylesheet">
    <link href="../../lib/bs5/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../lib/bs5/bootstrap-icons.css">

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f1f33 0%, #1e3a5f 40%, #2a5298 70%, #00b4d8 100%);
            background-size: 400% 400%;
            animation: gradientShift 12s ease infinite;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating shapes */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            opacity: 0.07;
            background: #fff;
        }
        body::before {
            width: 600px; height: 600px;
            top: -200px; right: -150px;
            animation: float1 20s ease-in-out infinite;
        }
        body::after {
            width: 400px; height: 400px;
            bottom: -100px; left: -100px;
            animation: float2 25s ease-in-out infinite;
        }
        @keyframes float1 {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-80px, 60px) rotate(180deg); }
        }
        @keyframes float2 {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(60px, -80px); }
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 1.5rem;
            z-index: 10;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            padding: 2.5rem 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-brand .icon-wrapper {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #1e3a5f, #00b4d8);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #fff;
            margin-bottom: 1rem;
            box-shadow: 0 8px 25px rgba(0, 180, 216, 0.35);
        }

        .login-brand h1 {
            font-weight: 800;
            font-size: 1.6rem;
            color: #0f1f33;
            letter-spacing: -0.5px;
            margin: 0;
        }

        .login-brand p {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            margin: 0.25rem 0 0;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 0.75rem 0.5rem 2.75rem;
            font-size: 0.9rem;
            font-weight: 500;
            height: 56px;
            transition: all 0.2s ease;
            background-color: #f8fafc;
        }

        .form-floating .form-control:focus {
            border-color: #00b4d8;
            box-shadow: 0 0 0 4px rgba(0, 180, 216, 0.12);
            background-color: #fff;
        }

        .form-floating label {
            padding-left: 2.75rem;
            color: #94a3b8;
            font-weight: 500;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
            color: #94a3b8;
            font-size: 1.1rem;
            transition: color 0.2s;
        }

        .form-floating:focus-within .input-icon {
            color: #00b4d8;
        }

        .form-check {
            margin-bottom: 1.25rem;
        }

        .form-check-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #64748b;
        }

        .form-check-input:checked {
            background-color: #00b4d8;
            border-color: #00b4d8;
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #1e3a5f, #2a5298);
            color: #fff;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(30, 58, 95, 0.35);
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #2a5298, #1e3a5f);
            box-shadow: 0 6px 25px rgba(30, 58, 95, 0.5);
            transform: translateY(-2px);
            color: #fff;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 12px;
            border: none;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.75rem 1rem;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.78rem;
            font-weight: 500;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 5;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0;
            font-size: 1.1rem;
        }

        .password-toggle:hover {
            color: #00b4d8;
        }

        .is-invalid {
            border-color: #ef4444 !important;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-brand">
            <div class="icon-wrapper">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <h1>DMS</h1>
            <p>Sənəd İdarəetmə Sistemi</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger mb-3">
                <i class="bi bi-exclamation-circle me-1"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="form-floating position-relative">
                <i class="bi bi-person input-icon"></i>
                <input type="text" 
                       class="form-control @error('username') is-invalid @enderror" 
                       id="username" 
                       name="username" 
                       placeholder="İstifadəçi adı" 
                       value="{{ old('username') }}"
                       autocomplete="username"
                       autofocus
                       required>
                <label for="username">İstifadəçi adı</label>
            </div>

            <div class="form-floating position-relative">
                <i class="bi bi-lock input-icon"></i>
                <input type="password" 
                       class="form-control @error('password') is-invalid @enderror" 
                       id="password" 
                       name="password" 
                       placeholder="Şifrə"
                       autocomplete="current-password"
                       required>
                <label for="password">Şifrə</label>
                <button type="button" class="password-toggle" onclick="togglePassword()">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
            </div>

            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">Məni xatırla</label>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right me-1"></i> Daxil ol
            </button>
        </form>
    </div>

    <div class="login-footer">
        &copy; {{ date('Y') }} DMS &mdash; Document Management System
    </div>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>
</body>
</html>
