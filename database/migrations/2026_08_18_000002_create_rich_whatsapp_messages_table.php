<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rich_whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->string('request_id', 128)->nullable()->index();
            $table->string('whatsapp_message_id', 128)->nullable()->index();
            $table->string('direction', 16);
            $table->string('status', 24)->default('received');
            $table->string('from_phone', 32)->nullable()->index();
            $table->string('to_phone', 32)->nullable()->index();
            $table->text('body')->nullable();
            $table->string('media_type', 24)->nullable();
            $table->string('media_name', 255)->nullable();
            $table->string('media_path_or_reference', 500)->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Node retries re-deliver the same whatsapp_message_id; avoid dupes.
            $table->unique(['whatsapp_message_id'], 'rwa_messages_wa_id_unique');

            $table->index(['conversation_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rich_whatsapp_messages');
    }
};