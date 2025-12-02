<?php

namespace App\Http\Controllers;

use App\Models\Detection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 10 log terbaru
        $logs = Detection::orderByDesc('detected_at')
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $last = $logs->first();

        // ======== Status online berdasar event terakhir (<= 5 menit) ========
        $lastAt = $last?->detected_at ?? $last?->created_at;
        $isOnline = $lastAt ? now()->diffInMinutes($lastAt) <= 5 : false;

        // ======== Grafik 7 hari terakhir ========
        $today = now()->startOfDay();
        $startDaily = (clone $today)->subDays(6);

        $rawDaily = Detection::selectRaw("DATE(COALESCE(detected_at, created_at)) as d, COUNT(*) as total")
            ->whereDate(DB::raw("COALESCE(detected_at, created_at)"), '>=', $startDaily->toDateString())
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $dailyLabels = [];
        $dailyValues = [];
        for ($i = 0; $i < 7; $i++) {
            $date = (clone $startDaily)->addDays($i);
            $key  = $date->toDateString();
            $found = $rawDaily->firstWhere('d', $key);
            $dailyLabels[] = $date->format('d M');
            $dailyValues[] = $found ? (int)$found->total : 0;
        }

        // ======== Grafik 6 bulan terakhir ========
        $startMonthly = now()->startOfMonth()->subMonths(5);
        $rawMonthly = Detection::selectRaw("DATE_FORMAT(COALESCE(detected_at, created_at), '%Y-%m') as ym, COUNT(*) as total")
            ->where(DB::raw("COALESCE(detected_at, created_at)"), '>=', $startMonthly->toDateString())
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $monthlyLabels = [];
        $monthlyValues = [];
        for ($i = 0; $i < 6; $i++) {
            $m = (clone $startMonthly)->addMonths($i);
            $ym = $m->format('Y-m');
            $found = $rawMonthly->firstWhere('ym', $ym);
            $monthlyLabels[] = $m->translatedFormat('M Y');
            $monthlyValues[] = $found ? (int)$found->total : 0;
        }

        // ======== Breakdown sensor (RCWL / PIR / IR) ========
        $sensorCounts = ['rcwl' => 0, 'pir' => 0, 'ir' => 0];
        $rowsSensor = Detection::select('sensor_type', DB::raw('COUNT(*) as total'))
            ->groupBy('sensor_type')
            ->get();
        foreach ($rowsSensor as $r) {
            $key = strtolower($r->sensor_type);
            if (isset($sensorCounts[$key])) $sensorCounts[$key] = (int)$r->total;
        }

        // ======== Rasio siang/malam ========
        $modeCounts = ['Siang' => 0, 'Malam' => 0];
        $rowsMode = Detection::selectRaw("IF(is_daytime, 'Siang', 'Malam') as mode, COUNT(*) as total")
            ->groupBy('mode')
            ->get();
        foreach ($rowsMode as $r) {
            $modeCounts[$r->mode] = (int)$r->total;
        }

        // ======== Ringkasan angka ========
        $totalToday = Detection::whereDate(DB::raw("COALESCE(detected_at, created_at)"), now()->toDateString())->count();
        $totalThisMonth = Detection::whereMonth(DB::raw("COALESCE(detected_at, created_at)"), now()->month)
            ->whereYear(DB::raw("COALESCE(detected_at, created_at)"), now()->year)
            ->count();
        $totalAll = Detection::count();

        return view('dashboard', [
            'last'           => $last,
            'logs'           => $logs,
            'isOnline'       => $isOnline,
            'dailyLabels'    => $dailyLabels,
            'dailyValues'    => $dailyValues,
            'monthlyLabels'  => $monthlyLabels,
            'monthlyValues'  => $monthlyValues,
            'sensorCounts'   => $sensorCounts,
            'modeCounts'     => $modeCounts,
            'totalToday'     => $totalToday,
            'totalThisMonth' => $totalThisMonth,
            'totalAll'       => $totalAll,
        ]);
    }
}
