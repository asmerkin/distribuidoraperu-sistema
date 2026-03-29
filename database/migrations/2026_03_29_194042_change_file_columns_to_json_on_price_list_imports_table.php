<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing single-value rows to JSON arrays
        DB::table('price_list_imports')->get()->each(function ($row) {
            DB::table('price_list_imports')->where('id', $row->id)->update([
                'file_path' => json_encode([$row->file_path]),
                'file_name' => json_encode([$row->file_name]),
            ]);
        });

        Schema::table('price_list_imports', function (Blueprint $table) {
            $table->json('file_path')->change();
            $table->json('file_name')->change();
            $table->dropColumn('file_type');
        });
    }

    public function down(): void
    {
        Schema::table('price_list_imports', function (Blueprint $table) {
            $table->string('file_path')->change();
            $table->string('file_name')->change();
            $table->string('file_type')->after('file_name');
        });
    }
};
