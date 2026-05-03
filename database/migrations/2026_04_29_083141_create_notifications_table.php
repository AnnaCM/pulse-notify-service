<?php

use App\Support\Enums\NotificationPriority;
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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('batch_id')->nullable()->index();

            $table->string('channel');
            $table->string('recipient');

            $table->text('content');

            $table->string('status')->index();
            $table->string('priority')->default(NotificationPriority::NORMAL->value);

            $table->unsignedInteger('attempts')->default(0);

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();

            $table->string('external_id')->nullable();

            $table->string('idempotency_key')->nullable()->unique();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
