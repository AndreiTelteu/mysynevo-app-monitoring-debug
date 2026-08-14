<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique();
            $table->string('session_id');
            $table->unsignedBigInteger('sequence');
            $table->string('reason', 32);
            $table->string('environment_key');
            $table->string('environment_base_url');
            $table->string('platform', 10);
            $table->string('app_version');
            $table->string('build_number');
            $table->string('app_state', 16);
            $table->string('manufacturer')->nullable();
            $table->string('hardware_model')->nullable();
            $table->string('model_name')->nullable();
            $table->string('device_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('network_type', 16);
            $table->boolean('network_connected')->nullable();
            $table->boolean('network_internet_reachable')->nullable();
            $table->string('cellular_generation', 4)->nullable();
            $table->string('carrier')->nullable();
            $table->boolean('connection_expensive')->nullable();
            $table->text('navigation_url')->nullable();
            $table->ipAddress('source_ip')->nullable();
            $table->timestamp('last_seen_at')->index();
            $table->boolean('is_pinned')->default(false)->index();
            $table->timestamps();

            $table->index(['session_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_devices');
    }
};
