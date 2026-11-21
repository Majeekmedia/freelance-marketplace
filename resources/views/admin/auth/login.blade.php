<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin sign in — {{ \App\Support\SiteSettings::brandName() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root{
            --paper:#FBFAF7; --ink:#16140F; --ink-2:#4C473C; --ink-3:#7E7869;
            --rule:#E1DBCD; --rule-2:#CFC8B6;
            --forest:#1C4A3B; --forest-2:#2A6450; --forest-wash:#E9F0EC;
            --danger:#8C2F1B; --danger-wash:#FBEDEA;
            --shell:#141310; --shell-2:#1F1D18; --shell-ink-2:#A8A092; --shell-accent:#7FB09C;
            --serif:"Instrument Serif",Georgia,serif;
            --sans:"Instrument Sans",-apple-system,"Segoe UI",Roboto,sans-serif;
        }
        *,*::before,*::after{box-sizing:border-box}
        body{
            margin:0;min-height:100vh;
            display:grid;place-items:center;
            padding:2rem 1rem;
            background:var(--shell);color:var(--ink);
            font-family:var(--sans);font-size:16px;line-height:1.55;
            -webkit-font-smoothing:antialiased;
        }
        a{color:inherit;text-decoration:none}
        :focus-visible{outline:2px solid var(--shell-accent);outline-offset:3px;border-radius:3px}

        .panel{
            width:100%;max-width:410px;
            background:var(--paper);
            border-radius:14px;
            padding:2rem 1.9rem 1.7rem;
            box-shadow:0 24px 60px rgba(0,0,0,.45);
        }
        .brand{display:flex;align-items:center;gap:.55rem;margin-bottom:1.5rem}
        .mark{
            width:28px;height:28px;border-radius:7px;flex:none;
            background:var(--forest);color:#fff;
            display:grid;place-items:center;
            font-size:.65rem;font-weight:700;letter-spacing:.02em;
        }
        .brand-name{font-family:var(--serif);font-size:1.05rem}
        .brand-scope{
            display:block;font-size:.6rem;letter-spacing:.14em;text-transform:uppercase;
            color:var(--ink-3);font-weight:600;margin-top:.05rem;
        }

        h1{font-family:var(--serif);font-weight:400;font-size:1.6rem;letter-spacing:-.02em;margin:0 0 .3rem;line-height:1.1}
        .sub{color:var(--ink-3);font-size:.85rem;margin:0 0 1.5rem;line-height:1.5}

        .alert{
            padding:.7rem .85rem;border-radius:6px;font-size:.83rem;
            background:var(--danger-wash);color:var(--danger);
            margin-bottom:1.1rem;line-height:1.5;
        }
        .alert ul{margin:0;padding-left:1.05rem}

        .field{margin-bottom:1rem}
        label{display:block;font-size:.79rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem}
        input[type="email"],input[type="password"]{
            width:100%;padding:.62rem .72rem;
            border:1px solid var(--rule-2);border-radius:6px;
            font:inherit;font-size:.9rem;background:#fff;color:var(--ink);
        }
        input:focus{outline:2px solid var(--forest);outline-offset:1px;border-color:var(--forest)}

        .remember{display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:var(--ink-2);margin-bottom:1.2rem;cursor:pointer}
        .remember input{width:15px;height:15px;margin:0}

        .submit{
            width:100%;padding:.65rem .9rem;border:0;border-radius:6px;
            background:var(--forest);color:#fff;
            font:inherit;font-size:.88rem;font-weight:500;cursor:pointer;
        }
        .submit:hover{background:var(--forest-2)}

        .foot{
            margin-top:1.4rem;padding-top:1.1rem;border-top:1px solid var(--rule);
            font-size:.76rem;color:var(--ink-3);line-height:1.55;
        }
        .foot a{text-decoration:underline}
    </style>
</head>
<body>
    <div class="panel">
        <div class="brand">
            <span class="mark" aria-hidden="true">{{ \App\Support\SiteSettings::initials() }}</span>
            <span>
                <span class="brand-name">{{ \App\Support\SiteSettings::brandName() }}</span>
                <span class="brand-scope">Admin console</span>
            </span>
        </div>

        <h1>Sign in</h1>
        <p class="sub">
            This is the administrator sign-in. Customer accounts cannot be used here.
        </p>

        @if ($errors->any())
            <div class="alert" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf

            <div class="field">
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" required autofocus
                       autocomplete="username" value="{{ old('email') }}">
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required
                       autocomplete="current-password">
            </div>

            <label class="remember">
                <input type="checkbox" name="remember" value="1">
                Keep me signed in on this device
            </label>

            <button class="submit" type="submit">Sign in to admin</button>
        </form>

        <p class="foot">
            <a href="{{ route('admin.password.request') }}">Forgot your password?</a>
            <span style="margin:0 .35rem;color:var(--rule-2)">·</span>
            Looking for your own account? <a href="{{ route('login') }}">Customer sign-in</a>.
        </p>
    </div>
</body>
</html>
