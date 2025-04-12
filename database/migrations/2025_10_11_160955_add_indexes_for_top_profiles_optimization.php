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
        Schema::table('users', function (Blueprint $table) {
            // Критичный составной индекс для top-profiles запроса
            // Покрывает WHERE условия: mode, gender (для JOIN), lat/long (NOT NULL проверки), registration_date
            $table->index(['mode', 'gender', 'lat', 'long', 'registration_date'], 'idx_users_top_profiles');
        });

        Schema::table('user_preferences', function (Blueprint $table) {
            // Обратный индекс для эффективного JOIN с user_preferences
            // Позволяет быстро найти пользователей по gender и отфильтровать по user_id
            $table->index(['gender', 'user_id'], 'idx_preferences_gender_user');
        });

        Schema::table('user_information', function (Blueprint $table) {
            // Индекс для подзапроса superboom_due_date в user_information
            $table->index(['user_id', 'superboom_due_date'], 'idx_user_information_superboom');
        });

        Schema::table('user_images', function (Blueprint $table) {
            // Покрывающий индекс для выбора первого изображения
            // Покрывает подзапрос: SELECT image FROM user_images WHERE user_id = ? ORDER BY id LIMIT 1
            $table->index(['user_id', 'id'], 'idx_user_images_userid_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_top_profiles');
        });

        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropIndex('idx_preferences_gender_user');
        });

        Schema::table('user_information', function (Blueprint $table) {
            $table->dropIndex('idx_user_information_superboom');
        });

        Schema::table('user_images', function (Blueprint $table) {
            $table->dropIndex('idx_user_images_userid_id');
        });
    }
};
