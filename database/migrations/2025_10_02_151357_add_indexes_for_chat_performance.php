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
        Schema::table('chat_messages', function (Blueprint $table) {
            // на получение сообщений в разговоре
            $table->index(['conversation_id', 'id'], 'idx_chat_messages_conversation_id_id');

            // на получение непрочитанных сообщений пользователя в разговоре
            $table->index(['receiver_id', 'is_seen', 'conversation_id'], 'idx_chat_messages_receiver_unread');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex('idx_chat_messages_conversation_id_id');
            $table->dropIndex('idx_chat_messages_receiver_unread');
        });
    }
};
