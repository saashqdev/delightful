<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 表存inthennotexecute
        if (Schema::hasTable('delightful_api_premium_exceptions')) {
            return;
        }

        Schema::create('delightful_api_premium_exceptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('exception_type', 255)->comment('exceptiontype');
            $table->boolean('can_retry')->comment('whethercanretry')->nullable();
            $table->integer('retry_max_times')->comment('retrymost大count')->nullable();
            $table->integer('retry_interval')->comment('retrytimebetween隔')->nullable();
            $table->datetimes();
            $table->comment('exceptioninformation表，saveexceptiontype，whethercanretry，retrymost大count，retrytimebetween隔');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_premium_exceptions');
    }
};
