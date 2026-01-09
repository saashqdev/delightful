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
        if (Schema::hasTable('delightful_api_premium_resources')) {
            return;
        }
        Schema::create('delightful_api_premium_resources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('endpoint_id', 64)->comment('接入pointID');
            $table->string('resource_name', 64)->comment('resourcename');
            $table->integer('billing_cycle_value')->default(0)->comment('计费periodvalue');
            $table->tinyInteger('billing_cycle_type')->default(0)->comment('0: 总quantity, 1:second, 2:minute钟, 3:hour, 4:day');
            $table->integer('total_usage')->default(0)->comment('总quantity');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['endpoint_id', 'id'], 'index_endpoint_id');
            $table->comment('APIresource计费rule表,support总quantityorspeedrate计费');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_premium_resources');
    }
};
