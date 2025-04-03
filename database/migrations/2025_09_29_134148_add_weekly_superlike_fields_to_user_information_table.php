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
            // Add column for tracking when user last received weekly superlikes  
            if (!Schema::hasColumn('user_information', 'superlikes_last_reset')) {
                $table->date('superlikes_last_reset')->nullable()->after('superlikes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_information', function (Blueprint $table) {
            if (Schema::hasColumn('user_information', 'superlikes_last_reset')) {
                $table->dropColumn('superlikes_last_reset');
            }
        });
    }
};
