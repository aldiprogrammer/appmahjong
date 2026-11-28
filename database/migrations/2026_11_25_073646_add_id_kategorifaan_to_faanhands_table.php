<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('faanhands', function (Blueprint $table) {
            $table->string('id_kategorifaan', 11)->nullable()->after('id_aturanmahjong');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faanhands', function (Blueprint $table) {
            $table->dropColumn('id_kategorifaan');
        });
    }
};
