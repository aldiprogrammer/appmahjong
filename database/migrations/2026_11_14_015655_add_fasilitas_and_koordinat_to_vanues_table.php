<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vanues', function (Blueprint $table) {
            $table->json('fasilitas')->nullable()->after('alamat');
            $table->decimal('latitude', 10, 7)->nullable()->after('fasilitas');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('vanues', function (Blueprint $table) {
            $table->dropColumn(['fasilitas', 'latitude', 'longitude']);
        });
    }
};
