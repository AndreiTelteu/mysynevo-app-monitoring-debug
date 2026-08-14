<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_devices', function (Blueprint $table) {
            $table->string('salesforce_contact_key')->nullable()->after('navigation_url');
            $table->string('salesforce_device_id')->nullable()->after('salesforce_contact_key');
        });
    }

    public function down(): void
    {
        Schema::table('live_devices', function (Blueprint $table) {
            $table->dropColumn(['salesforce_contact_key', 'salesforce_device_id']);
        });
    }
};
