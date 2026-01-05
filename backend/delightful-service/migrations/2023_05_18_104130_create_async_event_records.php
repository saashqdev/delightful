<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateAsyncEventRecords extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('async_event_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event', 255)->comment('Event');
            $table->string('listener', 255)->comment('Listener');
            $table->tinyInteger('status')->default(0)->comment('Execution status: 0 pending, 1 running, 2 finished, 3 retries exceeded');
            $table->tinyInteger('retry_times')->default(0)->comment('Retry attempts');
            $table->longText('args')->comment('Event payload (currently serialize($event))');
            $table->timestamps();
            $table->index(['status', 'updated_at']);

            $table->comment('Async event records');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('async_event_records');
    }
}
