<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rich_whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_chat_id', 64)->unique();
            $table->string('phone', 32)->nullable()->index();
            $table->string('display_name', 255)->nullable();
            $table->string('profile_name', 255)->nullable();
            $table->text('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->string('last_message_direction', 16)->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->boolean('is_archived')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['is_archived', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rich_whatsapp_conversations');
    }
};