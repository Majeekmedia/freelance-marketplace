<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') — {{ \App\Support\SiteSettings::brandName() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root{
            --paper:#FBFAF7; --paper-2:#F4F1E9; --paper-3:#EDE9DE;
            --ink:#16140F; --ink-2:#4C473C; --ink-3:#7E7869;
            --rule:#E1DBCD; --rule-2:#CFC8B6;
            --forest:#1C4A3B; --forest-2:#2A6450; --forest-wash:#E9F0EC;
            --amber:#8A5A16; --amber-wash:#F7EEDC;
            --radius:10px; --radius-sm:6px;
            --serif:"Instrument Serif",Georgia,serif;
            --sans:"Instrument Sans",-apple-system,"Segoe UI",Roboto,sans-serif;
        }
        *,*::before,*::after{box-sizing:border-box}
        body{
            margin:0;background:var(--paper);color:var(--ink);
            font-family:var(--sans);font-size:16px;line-height:1.55;
            -webkit-font-smoothing:antialiased;
            min-height:100vh;display:grid;place-items:center;
            padding:2rem 1.25rem;
        }
        a{color:inherit;text-decoration:none}
        :focus-visible{outline:2px solid var(--forest);outline-offset:3px;border-radius:3px}
        .sr-only{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}

        .auth{width:100%;max-width:27rem}
        .brand{display:flex;align-items:center;gap:.6rem;font-family:var(--serif);font-size:1.3rem;margin-bottom:2rem}
        .brand-mark{
            width:26px;height:26px;border-radius:5px;background:var(--forest);
            display:grid;place-items:center;color:#fff;font-family:var(--sans);
            font-size:.72rem;font-weight:600;flex:none;
        }
        .brand-logo{height:26px;width:auto}

        .card{
            background:#fff;border:1px solid var(--rule);border-radius:14px;
            padding:1.75rem;box-shadow:0 2px 12px rgba(22,20,15,.05);
        }
        h1{font-family:var(--serif);font-weight:400;font-size:1.85rem;line-height:1.1;letter-spacing:-.02em;margin:0 0 .4rem}
        .sub{color:var(--ink-3);font-size:.875rem;margin:0 0 1.5rem}

        .field{margin-bottom:1rem}
        .field label{display:block;font-size:.8rem;font-weight:500;color:var(--ink-2);margin-bottom:.35rem}
        .field input{
            width:100%;padding:.62rem .7rem;font:inherit;font-size:.9rem;
            border:1px solid var(--rule-2);border-radius:var(--radius-sm);
            background:#fff;color:var(--ink);
        }
        .field input:focus{outline:2px solid var(--forest);outline-offset:0;border-color:var(--forest)}
        .field .hint{font-size:.75rem;color:var(--ink-3);margin-top:.3rem}

        .btn{
            display:inline-flex;align-items:center;justify-content:center;gap:.5em;
            padding:.68rem 1.1rem;border-radius:var(--radius-sm);
            font:inherit;font-size:.9rem;font-weight:500;
            border:1px solid transparent;cursor:pointer;width:100%;
            background:var(--forest);color:#fff;transition:background .15s ease;
        }
        .btn:hover{background:var(--forest-2)}
        .btn-secondary{background:none;border-color:var(--rule-2);color:var(--ink)}
        .btn-secondary:hover{background:var(--paper-2);border-color:var(--ink-3)}

        .alert{
            padding:.7rem .85rem;border-radius:var(--radius-sm);
            font-size:.845rem;margin-bottom:1.15rem;line-height:1.45;
        }
        .alert-error{background:#FBEDEA;color:#8C2F1B}
        .alert-ok{background:var(--forest-wash);color:var(--forest)}
        .alert-note{background:var(--amber-wash);color:var(--amber)}
        .alert ul{margin:.3rem 0 0;padding-left:1.1rem}

        .alt{text-align:center;font-size:.845rem;color:var(--ink-3);margin-top:1.25rem}
        .alt a{color:var(--forest);font-weight:500;text-decoration:underline;text-underline-offset:2px}

        .choice{display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-bottom:1.1rem}
        .choice input{position:absolute;opacity:0;width:0;height:0}
        .choice label{
            border:1px solid var(--rule-2);border-radius:var(--radius);
            padding:.8rem .85rem;cursor:pointer;display:block;
            transition:border-color .15s ease,background .15s ease;
        }
        .choice label:hover{border-color:var(--ink-3)}
        .choice input:checked + label{border-color:var(--forest);background:var(--forest-wash)}
        .choice input:focus-visible + label{outline:2px solid var(--forest);outline-offset:2px}
        .choice b{display:block;font-size:.875rem;font-weight:600;margin-bottom:.15rem}
        .choice span{display:block;font-size:.75rem;color:var(--ink-3);line-height:1.4}

        .row-between{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.1rem;font-size:.83rem}
        .checkbox{display:inline-flex;align-items:center;gap:.45rem;color:var(--ink-2)}
        .checkbox input{width:auto;margin:0}
    </style>
</head>
<body>
    <main class="auth">
        <a class="brand" href="{{ route('home') }}">
            @if(\App\Support\LogoUploader::url())
                <img class="brand-logo" src="{{ \App\Support\LogoUploader::url() }}"
                     alt="{{ \App\Support\SiteSettings::brandName() }}">
            @else
                <span class="brand-mark" aria-hidden="true">{{ \App\Support\SiteSettings::initials() }}</span>
            @endif
            {{ \App\Support\SiteSettings::brandName() }}
        </a>

        @yield('content')
    </main>
</body>
</html>
