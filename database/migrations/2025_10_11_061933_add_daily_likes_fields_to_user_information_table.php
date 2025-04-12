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
            if (!Schema::hasColumn('user_information', 'daily_likes')) {
                $table->integer('daily_likes')->default(30)->after('purchased_superbooms');
            }
            if (!Schema::hasColumn('user_information', 'daily_likes_last_reset')) {
                $table->date('daily_likes_last_reset')->nullable()->after('daily_likes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_information', function (Blueprint $table) {
            if (Schema::hasColumn('user_information', 'daily_likes_last_reset')) {
                $table->dropColumn('daily_likes_last_reset');
            }
            if (Schema::hasColumn('user_information', 'daily_likes')) {
                $table->dropColumn('daily_likes');
            }
        });
    }
};
