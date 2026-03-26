<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scanner_devices', function (Blueprint $table) {
            $table->string('user_agent')->nullable()->after('last_used_at');
        });
    }

    public function down(): void
    {
        Schema::table('scanner_devices', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });
    }
};
