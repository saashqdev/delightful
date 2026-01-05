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
            $table->tinyInteger('status')->default(0)->comment('Event execution status  0:Pending; 1:Executing; 2:Completed; 3:Exceeded retry limit;');
            $table->tinyInteger('retry_times')->default(0)->comment('Retry times');
            $table->longText('args')->comment('Event parameters, currently serialize($event)');
            $table->timestamps();
            $table->index(['status', 'updated_at']);

            $table->comment('Async event records table');
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
