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
        Schema::table('penggunas', function (Blueprint $table) {
            $table->string('google_id', 100)->nullable()->unique()->after('id');
            $table->string('avatar')->nullable()->after('email');
            $table->string('email', 255)->change();
            $table->string('nowa', 50)->nullable()->change();
            $table->string('password', 200)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penggunas', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'avatar']);
            $table->string('email', 50)->change();
            $table->string('nowa', 50)->nullable(false)->change();
            $table->string('password', 200)->nullable(false)->change();
        });
    }
};
