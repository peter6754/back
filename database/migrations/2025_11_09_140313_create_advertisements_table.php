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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('link')->nullable();
            $table->integer('impressions_limit')->default(0); // Лимит показов (0 = безлимит)
            $table->integer('impressions_count')->default(0); // Текущее количество показов
            $table->dateTime('start_date')->nullable(); // Дата начала показа
            $table->dateTime('end_date')->nullable(); // Дата окончания показа
            $table->boolean('is_active')->default(true); // Активна ли реклама
            $table->integer('order')->default(0); // Порядок показа
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};
