<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('price_list_imports', function (Blueprint $table) {
            $table->json('draft_data')->nullable()->after('items_linked');
        });

        DB::table('price_list_imports')
            ->where('status', 'pending')
            ->update(['status' => 'uploading']);
    }

    public function down(): void
    {
        Schema::table('price_list_imports', function (Blueprint $table) {
            $table->dropColumn('draft_data');
        });
    }
};
