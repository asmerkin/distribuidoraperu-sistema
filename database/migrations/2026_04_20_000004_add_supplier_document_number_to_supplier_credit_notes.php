<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_credit_notes', function (Blueprint $table) {
            $table->string('supplier_document_number')->nullable()->after('credit_note_number');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_credit_notes', function (Blueprint $table) {
            $table->dropColumn('supplier_document_number');
        });
    }
};
