<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Detection;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{
    public function store(Request $request)
    {
        // kamu bisa tambah auth token kalau mau
        $data = $request->validate([
            'sensor_type' => 'required|string', // rcwl / pir / ir
            'is_daytime'  => 'required|boolean',
            'message'     => 'nullable|string',
            'actuator'    => 'nullable|string',
            'detected_at' => 'nullable|date',
        ]);

        // kalau ESP32 nggak ngirim detected_at, pakai sekarang
        if (empty($data['detected_at'])) {
            $data['detected_at'] = now();
        }

        Detection::create($data);

        return response()->json(['status' => 'ok']);
    }

    // buat di-polling ajax
    public function latest()
    {
        $last = Detection::orderByDesc('detected_at')
            ->orderByDesc('id')
            ->first();

        return response()->json($last);
    }
}
