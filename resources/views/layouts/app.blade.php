<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Sawah IoT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700" rel="stylesheet" />

    <style>
        :root{
            --bg:#0b1020;
            --glass:rgba(13,18,36,.5);
            --glass-strong:rgba(13,18,36,.72);
            --border:rgba(148,163,184,.14);
            --muted:#94a3b8;
            --text:#e2e8f0;
            --accent:#60a5fa;
            --accent2:#38bdf8;
            --success:#22c55e;
            --danger:#ef4444;
            --radius:16px;
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            font-family:"Figtree",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
            color:var(--text);
            background:
              radial-gradient(1200px 600px at -10% -10%, #12244e 0%, rgba(0,0,0,0) 50%),
              radial-gradient(900px 600px at 110% 10%, #0b3a58 0%, rgba(0,0,0,0) 45%),
              linear-gradient(180deg, #060914 0%, #0b1020 100%);
            min-height:100vh;
            display:flex;
        }
        .sidebar{
            width:240px;
            padding:18px 14px;
            backdrop-filter:saturate(140%) blur(10px);
            background:var(--glass-strong);
            border-right:1px solid var(--border);
            position:sticky; top:0; height:100vh;
        }
        .brand{font-weight:700; display:flex; gap:.5rem; align-items:center; margin-bottom:14px}
        .brand b{background:linear-gradient(90deg,var(--accent),var(--accent2)); -webkit-background-clip:text; background-clip:text; color:transparent}
        .menu{
            margin-top:10px;
        }
        .menu .title{
            font-size:.62rem; color:rgba(226,232,240,.6); letter-spacing:.12em; text-transform:uppercase; margin:10px 6px 6px;
        }
        .menu a{
            display:flex; align-items:center; gap:.55rem;
            text-decoration:none; color:var(--text);
            padding:9px 10px; border-radius:12px; margin:3px 4px;
            border:1px solid transparent;
        }
        .menu a.active, .menu a:hover{
            background:rgba(96,165,250,.08);
            border-color:rgba(96,165,250,.15);
        }

        .main{flex:1; display:flex; flex-direction:column; min-height:100vh;}
        .topbar{
            display:flex; justify-content:space-between; align-items:center;
            padding:12px 20px; border-bottom:1px solid var(--border);
            backdrop-filter: blur(8px);
            background:linear-gradient(180deg, rgba(9,13,26,.4), rgba(9,13,26,.2));
        }
        .container{max-width:1280px; margin:0 auto; padding:18px 20px 28px; width:100%}

        .hero{
            display:flex; gap:14px; align-items:stretch; margin-top:8px;
        }
        .hero-card{
            flex:1; border:1px solid var(--border); border-radius:var(--radius);
            background:linear-gradient(180deg, rgba(96,165,250,.10), rgba(56,189,248,.06));
            padding:16px 18px;
            box-shadow:0 18px 40px rgba(0,0,0,.18);
        }
        .hero-side{
            width:360px; border:1px solid var(--border); border-radius:var(--radius);
            background:var(--glass);
            padding:16px 18px;
            display:flex; flex-direction:column; gap:10px;
        }
        .chip{
            display:inline-flex; align-items:center; gap:8px; padding:6px 10px;
            background:rgba(148,163,184,.12); border:1px solid var(--border);
            border-radius:999px; font-size:.75rem; color:#cbd5e1;
        }
        .dot{width:8px; height:8px; border-radius:50%;}
        .dot.success{background:var(--success)}
        .dot.danger{background:var(--danger)}

        .metrics{display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; margin-top:14px;}
        .metric{
            background:var(--glass); border:1px solid var(--border); border-radius:14px;
            padding:12px 14px;
        }
        .metric .lbl{font-size:.7rem; color:var(--muted); text-transform:uppercase; letter-spacing:.06em;}
        .metric .val{font-size:1.6rem; font-weight:800; line-height:1.2; margin-top:4px;}
        .metric .sub{font-size:.7rem; color:var(--muted);}

        .grid{
            display:grid; gap:14px; margin-top:16px;
            grid-template-columns: 1.1fr .9fr;
        }
        .card{
            background:var(--glass); border:1px solid var(--border); border-radius:var(--radius);
            padding:14px 16px; box-shadow:0 18px 40px rgba(0,0,0,.12);
        }
        .card .title{font-size:.95rem; font-weight:700; margin-bottom:8px;}
        .charts{display:grid; gap:14px; grid-template-columns: 1fr 1fr;}
        .legend{display:flex; gap:10px; flex-wrap:wrap; margin-top:6px; font-size:.75rem; color:var(--muted);}
        .legend span{display:inline-flex; align-items:center; gap:6px;}
        .legend i{width:10px; height:10px; border-radius:3px; display:inline-block;}

        .table-wrap{max-height:360px; overflow:auto; border-radius:12px; border:1px solid var(--border);}
        table{width:100%; border-collapse:collapse;}
        thead th{position:sticky; top:0; background:#0b1224; z-index:2; font-size:.72rem; color:#9fb1cc; text-align:left; padding:9px 10px; border-bottom:1px solid var(--border);}
        tbody td{padding:9px 10px; font-size:.8rem; border-bottom:1px dashed rgba(148,163,184,.12);}
        tbody tr:nth-child(odd) td{background:rgba(148,163,184,.03)}

        @media (max-width:1100px){
            .grid{grid-template-columns:1fr}
            .hero{flex-direction:column}
            .hero-side{width:100%}
            .metrics{grid-template-columns:1fr}
            .charts{grid-template-columns:1fr}
            .sidebar{display:none}
        }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <aside class="sidebar">
        <div class="brand">📡 <b>Sawah IoT</b></div>
        <div class="menu">
            <div class="title">Menu</div>
            <a href="{{ route('dashboard') }}" class="active">📊 Dashboard</a>
            <!-- <a href="#">⚙️ Konfigurasi</a> -->
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div>
                <div style="font-weight:700">Dashboard Sawah</div>
                <div style="font-size:.75rem; color:var(--muted)">Monitoring hama burung & tikus</div>
            </div>
            <div style="font-size:.75rem; color:var(--muted)">{{ now()->format('d M Y H:i') }} WIB</div>
        </header>

        <main class="container">
            @yield('content')
        </main>

        @yield('scripts')
    </div>
</body>
</html>
