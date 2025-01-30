<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * @return void
     */
    public function up(): void
    {
        Schema::table('user_activity', function (Blueprint $table) {
            $table->index(['session_start', 'user_id'], 'idx_session_start_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('gender', 'idx_gender');
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down(): void
    {
        Schema::table('user_activity', function (Blueprint $table) {
            $table->dropIndex('idx_session_start_user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_gender');
        });
    }
};
