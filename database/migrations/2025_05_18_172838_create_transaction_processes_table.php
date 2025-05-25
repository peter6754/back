<?php

use Illuminate\Database\Migrations\Migration;
use App\Services\Payments\PaymentsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
//        Schema::create('transactions_process', function (Blueprint $table) {
//            $table->id(); // Автоинкрементный id (эквивалент int(11) NOT NULL AUTO_INCREMENT)
//            $table->string('subscription_id', 255)->nullable()->collation('utf8mb4_unicode_ci');
//            $table->string('subscriber_id', 255)->nullable()->collation('utf8mb4_unicode_ci');
//            $table->string('transaction_id', 63)->nullable()->collation('utf8mb4_unicode_ci');
//            $table->string('provider', 255)->nullable()->collation('utf8mb4_unicode_ci');
//            $table->string('user_id', 191)->nullable()->collation('utf8mb4_unicode_ci')->index('transactions_user_id_fkey');
//            $table->double('price')->nullable();
//            $table->string('type', 255)->nullable()->collation('utf8mb4_unicode_ci');
//            $table->string('status', 255)->default(PaymentsService::ORDER_STATUS_PENDING)->collation('utf8mb4_unicode_ci');
//            $table->string('email', 255)->nullable()->collation('utf8mb4_unicode_ci');
//            $table->timestamp('purchased_at', 3)->useCurrent();
//            $table->timestamp('updated_at', 3)->useCurrent();
//            $table->timestamp('created_at', 3)->useCurrent();
//        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions_process');
    }
};
