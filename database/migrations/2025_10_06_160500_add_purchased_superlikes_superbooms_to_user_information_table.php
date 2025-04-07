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
        Schema::table('user_information', function (Blueprint $table) {
            $table->integer('purchased_superlikes')->default(0)->after('superlikes_last_reset');
            $table->integer('purchased_superbooms')->default(0)->after('superbooms_last_reset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_information', function (Blueprint $table) {
            $table->dropColumn(['purchased_superlikes', 'purchased_superbooms']);
        });
    }
};
