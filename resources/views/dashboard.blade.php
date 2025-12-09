@extends('layouts.app')

@section('content')

    {{-- HERO --}}
    <div class="hero">
        <div class="hero-card">
            <div class="chip">
                <span class="dot {{ $isOnline ? 'success' : 'danger' }}"></span>
                {{ $isOnline ? 'Perangkat Online' : 'Perangkat Offline' }}
            </div>
            <h2 style="margin:.5rem 0 0; font-size:1.6rem">Monitoring Sawah – Status Terkini</h2>
            <p style="margin:.4rem 0 0; color:var(--muted); font-size:.9rem">
                Update terakhir:
                @if($last)
                    {{ ($last->detected_at ?? $last->created_at)->format('d M Y H:i:s') }} WIB
                @else
                    belum ada data
                @endif
            </p>

            <div class="metrics">
                <div class="metric">
                    <div class="lbl">Deteksi hari ini</div>
                    <div class="val">{{ $totalToday }}</div>
                    <div class="sub">Semua sensor</div>
                </div>
                <div class="metric">
                    <div class="lbl">Bulan ini</div>
                    <div class="val">{{ $totalThisMonth }}</div>
                    <div class="sub">Akumulasi {{ now()->format('M Y') }}</div>
                </div>
                <div class="metric">
                    <div class="lbl">Total data</div>
                    <div class="val">{{ $totalAll }}</div>
                    <div class="sub">Sejak awal pencatatan</div>
                </div>
            </div>
        </div>

        <div class="hero-side">
            <div style="display:flex; justify-content:space-between; align-items:center">
                <div class="chip">🌾 Sawah: <b style="margin-left:6px">– 1</b></div>
                <button id="btn-refresh" style="cursor:pointer; padding:8px 12px; border-radius:10px; border:1px solid var(--border); background:rgba(148,163,184,.1); color:#e2e8f0;">
                    🔄 Segarkan
                </button>
            </div>
            <div style="font-size:.8rem; color:var(--muted);">
                Sistem: deteksi burung (RCWL) → servo; tikus (PIR) → servo + buzzer.
                Data dikirim via HTTP POST dari ESP32.
            </div>
            <div style="display:flex; gap:10px; margin-top:8px;">
                <div class="chip">🕒 Mode &nbsp;
                    @if($last)
                        {!! $last->is_daytime ? '<b>Siang</b>' : '<b>Malam</b>' !!}
                    @else
                        <b>-</b>
                    @endif
                </div>
                <div class="chip">🧭 Sensor terakhir &nbsp;
                    @if($last)
                        <b>{{ strtoupper($last->sensor_type) }}</b>
                    @else
                        <b>-</b>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- GRAFIK BESAR --}}
    <div class="grid">
        <div class="card">
            <div class="title">Grafik 7 Hari Terakhir</div>
            <canvas id="dailyChart" height="120"></canvas>
        </div>
        <div class="card">
            <div class="title">Grafik 6 Bulan Terakhir</div>
            <canvas id="monthlyChart" height="120"></canvas>
        </div>
    </div>

    {{-- MINI CHARTS (BREAKDOWN) --}}
    <div class="card" style="margin-top:16px;">
        <div class="title">Komposisi Deteksi</div>
        <div class="charts">
            <div>
                <canvas id="sensorChart" height="140"></canvas>
                <div class="legend">
                    <span><i style="background:#4ade80"></i> RCWL (burung)</span>
                    <span><i style="background:#fde68a"></i> PIR (tikus)</span>
                    <!-- <span><i style="background:#fca5a5"></i> IR (tikus)</span> -->
                </div>
            </div>
            <div>
                <canvas id="modeChart" height="140"></canvas>
                <div class="legend">
                    <span><i style="background:#60a5fa"></i> Siang</span>
                    <span><i style="background:#a5b4fc"></i> Malam</span>
                </div>
            </div>
        </div>
    </div>

    {{-- TABEL LOG --}}
    <div class="card" style="margin-top:16px;">
        <div class="title">Log Deteksi Terbaru</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Sensor</th>
                        <th>Pesan</th>
                        <th>Aktuator</th>
                        <th>Mode</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->detected_at?->format('d M Y H:i:s') ?? $log->created_at->format('d M Y H:i:s') }} WIB</td>
                        <td style="text-transform:uppercase">{{ $log->sensor_type }}</td>
                        <td>{{ $log->message ?? '-' }}</td>
                        <td>{{ $log->actuator ?? '-' }}</td>
                        <td>{{ $log->is_daytime ? 'Siang' : 'Malam' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color:var(--muted)">Belum ada data.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection

@section('scripts')
<script>
    // Tombol refresh
    document.getElementById('btn-refresh')?.addEventListener('click', () => location.reload());

    // Data dari controller
    const dailyLabels   = @json($dailyLabels);
    const dailyValues   = @json($dailyValues);
    const monthlyLabels = @json($monthlyLabels);
    const monthlyValues = @json($monthlyValues);
    const sensorCounts  = @json($sensorCounts); // {rcwl, pir, ir}
    const modeCounts    = @json($modeCounts);   // {Siang, Malam}

    // Chart harian
    new Chart(document.getElementById('dailyChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Deteksi',
                data: dailyValues,
                tension: .35,
                fill: true,
                backgroundColor: 'rgba(56,189,248,0.16)',
                borderColor: 'rgba(56,189,248,1)',
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: '#e2e8f0'
            }]
        },
        options: {
            plugins: { legend: { display:false }},
            scales: {
                x: { grid: { display:false }, ticks: { color:'#9fb1cc' } },
                y: { beginAtZero:true, ticks:{ stepSize:1, color:'#9fb1cc' }, grid:{ color:'rgba(148,163,184,.08)' } }
            }
        }
    });

    // Chart bulanan
    new Chart(document.getElementById('monthlyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label:'Deteksi/Bulan',
                data: monthlyValues,
                backgroundColor:'rgba(165,180,252,.65)',
                borderColor:'rgba(165,180,252,1)',
                borderWidth:1.2,
                borderRadius:6
            }]
        },
        options: {
            plugins: { legend: { display:false }},
            scales: {
                x:{ grid:{ display:false }, ticks:{ color:'#9fb1cc' } },
                y:{ beginAtZero:true, ticks:{ stepSize:1, color:'#9fb1cc' }, grid:{ color:'rgba(148,163,184,.08)' } }
            }
        }
    });

    // Chart donut: sensor
    new Chart(document.getElementById('sensorChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['RCWL','PIR','IR'],
            datasets: [{
                data: [sensorCounts.rcwl, sensorCounts.pir, sensorCounts.ir],
                backgroundColor: ['#4ade80','#fde68a','#fca5a5'],
                borderColor: ['#1b2a1f','#2b2816','#2b1c1c'],
                borderWidth: 1
            }]
        },
        options: {
            plugins: { legend: { display:false }},
            cutout: '62%'
        }
    });

    // Chart donut: mode siang/malam
    new Chart(document.getElementById('modeChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Siang','Malam'],
            datasets: [{
                data: [modeCounts.Siang ?? 0, modeCounts.Malam ?? 0],
                backgroundColor: ['#60a5fa','#a5b4fc'],
                borderColor: ['#172033','#1b1e2d'],
                borderWidth: 1
            }]
        },
        options: {
            plugins: { legend: { display:false }},
            cutout: '62%'
        }
    });
    setInterval(() => location.reload(), 10000);
</script>
@endsection
ppp