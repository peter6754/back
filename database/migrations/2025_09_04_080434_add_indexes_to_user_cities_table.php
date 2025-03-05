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
        Schema::table('user_cities', function (Blueprint $table) {
            // Prefix index для быстрого поиска по началу названия города (LIKE 'prefix%')
            DB::statement('CREATE INDEX idx_user_cities_formatted_address_prefix ON user_cities (formatted_address(50))');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_cities', function (Blueprint $table) {
            // Удаляем prefix индекс
            DB::statement('DROP INDEX IF EXISTS idx_user_cities_formatted_address_prefix');

        });
    }
};
