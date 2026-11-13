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
        Schema::table('kategorimenus', function (Blueprint $table) {
            $table->foreignId('vanue_id')->nullable()->after('kategori')->constrained('vanues')->nullOnDelete();
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->foreignId('vanue_id')->nullable()->after('id_kategori')->constrained('vanues')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kategorimenus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vanue_id');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vanue_id');
        });
    }
};
