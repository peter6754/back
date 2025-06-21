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
        Schema::create('mail_queue', function (Blueprint $table) {
            $table->id();
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('subject');
            $table->text('html_body');
            $table->text('text_body')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->json('attachments')->nullable();
            $table->json('variables')->nullable(); // переменные для подстановки
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->integer('attempts')->default(0);
            $table->timestamp('send_after')->nullable(); // для отложенной отправки
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('mail_templates')->onDelete('set null');
            $table->index(['status', 'send_after']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_queue');
    }
};
