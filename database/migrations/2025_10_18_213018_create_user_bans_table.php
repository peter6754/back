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
        Schema::create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->boolean('is_permanent')->default(false);
            $table->timestamp('banned_until')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index(['user_id', 'is_permanent']);
            $table->index('banned_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_bans');
    }
};
