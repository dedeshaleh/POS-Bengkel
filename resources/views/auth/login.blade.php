<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e8edf4;
            --paper: #ffffff;
            --brand: #f97316;
            --brand-dark: #ea580c;
            --bg-main: #f1f5f9;
            --bg-side: #111827;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Inter", "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background: var(--bg-main);
            display: grid;
            grid-template-columns: minmax(320px, 460px) minmax(0, 1fr);
            -webkit-font-smoothing: antialiased;
        }
        .side {
            position: relative;
            overflow: hidden;
            background: linear-gradient(185deg, #0b1220 0%, #111827 50%, #1e2533 100%);
            color: #e2e8f0;
            padding: 52px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 16px;
        }
        .side::before {
            content: "";
            position: absolute;
            top: -120px; right: -120px;
            width: 320px; height: 320px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(249, 115, 22, .35), transparent 70%);
        }
        .side .brand-logo {
            width: 56px; height: 56px;
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 24px; color: #fff;
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            box-shadow: 0 12px 26px -8px rgba(249, 115, 22, .65);
            margin-bottom: 8px;
            position: relative; z-index: 1;
        }
        .side h1 { margin: 0; font-family: "Plus Jakarta Sans", "Inter", sans-serif; font-size: 32px; line-height: 1.1; letter-spacing: -.02em; position: relative; z-index: 1; }
        .side p { margin: 0; color: #94a3b8; position: relative; z-index: 1; }
        .side .features { list-style: none; padding: 0; margin: 18px 0 0; display: grid; gap: 12px; position: relative; z-index: 1; }
        .side .features li { display: flex; align-items: center; gap: 11px; color: #cbd5e1; font-size: 14px; font-weight: 500; }
        .side .features i { width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; background: rgba(249, 115, 22, .14); color: #fb923c; font-size: 13px; }
        .main {
            display: grid;
            place-items: center;
            padding: 20px;
        }
        .card {
            width: min(440px, 100%);
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 34px;
            box-shadow: 0 24px 60px -18px rgba(15, 23, 42, 0.18);
        }
        .card h2 { margin: 0; font-family: "Plus Jakarta Sans", "Inter", sans-serif; font-size: 25px; letter-spacing: -.02em; }
        .card p { color: var(--muted); margin: 8px 0 0; }
        form { display: grid; gap: 14px; margin-top: 22px; }
        label { display: grid; gap: 6px; font-size: 13px; font-weight: 700; }
        .field { position: relative; }
        .field i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; }
        input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border-radius: 11px;
            border: 1px solid var(--line);
            font: inherit;
            transition: border-color .16s ease, box-shadow .16s ease;
        }
        input:focus {
            outline: 0;
            border-color: #fb923c;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.16);
        }
        button {
            border: 0;
            border-radius: 11px;
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: #fff;
            padding: 13px 14px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 10px 22px -8px rgba(249, 115, 22, .6);
            transition: transform .12s ease, filter .15s ease;
            margin-top: 4px;
        }
        button:hover { filter: brightness(1.05); transform: translateY(-1px); }
        button.loading {
            opacity: .8;
            pointer-events: none;
        }
        .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, .45);
            border-top-color: #fff;
            border-radius: 999px;
            display: inline-block;
            margin-right: 8px;
            animation: spin .8s linear infinite;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .error {
            margin-top: 12px;
            border-left: 4px solid #b42318;
            background: #fff0ee;
            color: #b42318;
            padding: 10px 12px;
            border-radius: 6px;
        }
        .hint {
            margin-top: 14px;
            font-size: 12px;
            color: var(--muted);
        }
        .loading-indicator {
            position: fixed;
            right: 14px;
            bottom: 14px;
            z-index: 40;
            display: none;
            align-items: center;
            gap: 8px;
            background: #0f172a;
            color: #f8fafc;
            border-radius: 10px;
            padding: 10px 12px;
            box-shadow: 0 10px 30px rgba(2, 6, 23, .35);
            font-size: 12px;
        }
        .loading-indicator.show { display: inline-flex; }
        @media (max-width: 900px) {
            body { grid-template-columns: 1fr; }
            .side { padding: 30px 20px; }
            .main { padding: 14px; }
        }
    </style>
</head>
<body>
    <aside class="side">
        <span class="brand-logo"><i class="fa-solid fa-screwdriver-wrench"></i></span>
        <h1>Bengkel Berkah</h1>
        <p>POS &amp; Inventory Management</p>
        <ul class="features">
            <li><i class="fa-solid fa-boxes-stacked"></i> Inventory batch FIFO tracking</li>
            <li><i class="fa-solid fa-wallet"></i> Customer debt &amp; payment control</li>
            <li><i class="fa-solid fa-user-shield"></i> Role-based access management</li>
        </ul>
    </aside>
    <main class="main">
        <section class="card">
            <h2>Sign In</h2>
            <p>Access your workspace dashboard.</p>

            <form method="post" action="{{ route('login.post') }}">
                @csrf
                <label>Email
                    <span class="field"><i class="fa-solid fa-envelope"></i><input type="email" name="email" value="{{ old('email') }}" required autofocus></span>
                </label>
                <label>Password
                    <span class="field"><i class="fa-solid fa-lock"></i><input type="password" name="password" required></span>
                </label>
                <button type="submit"><i class="fa-solid fa-right-to-bracket"></i>Login</button>
            </form>

            <div class="hint">Seed user: user1@bengkelberkah.test / password</div>
        </section>
    </main>
    <div class="loading-indicator" id="loginLoadingIndicator" aria-live="polite"></div>
    <script>
        (function () {
            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: @json($errors->first())
                });
            @endif

            const indicator = document.getElementById('loginLoadingIndicator');
            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                const submit = form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
                if (!(submit instanceof HTMLElement) || submit.classList.contains('loading')) return;

                const label = (submit.textContent || '').trim() || 'Login';
                const loadingText = `Processing ${label}...`;
                submit.dataset.originalHtml = submit.innerHTML;
                submit.innerHTML = `<span class="spinner"></span>${loadingText}`;
                submit.classList.add('loading');
                if ('disabled' in submit) submit.disabled = true;

                if (indicator) {
                    indicator.classList.add('show');
                    indicator.innerHTML = `<span class="spinner"></span><span>${loadingText}</span>`;
                }
            });
        })();
    </script>
</body>
</html>
