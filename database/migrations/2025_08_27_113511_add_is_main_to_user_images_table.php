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

        Schema::table('user_images', function (Blueprint $table) {
            $table->boolean('is_main')->default(false)->after('image');
            $table->index(['user_id', 'is_main']);
        });

        $this->setFirstPhotoAsMain();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_images', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_main']);
            $table->dropColumn('is_main');
        });
    }

    /**
     * Устанавливает первое фото каждого пользователя как главное
     */
    private function setFirstPhotoAsMain(): void
    {
        $firstPhotos = DB::table('user_images')
            ->select('user_id', DB::raw('MIN(id) as first_photo_id'))
            ->groupBy('user_id')
            ->get();

        foreach ($firstPhotos as $firstPhoto) {
            DB::table('user_images')
                ->where('id', $firstPhoto->first_photo_id)
                ->update(['is_main' => true]);
        }
    }
};
