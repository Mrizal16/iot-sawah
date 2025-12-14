<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detections', function (Blueprint $table) {
            $table->id();
            $table->string('sensor_type'); // rcwl, pir
            $table->boolean('is_daytime')->default(true); // siang/malam
            $table->string('message')->nullable(); // misal: "Burung terdeteksi", "Tikus terdeteksi"
            $table->string('actuator')->nullable(); // servo / buzzer
            $table->timestamp('detected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detections');
    }
};
