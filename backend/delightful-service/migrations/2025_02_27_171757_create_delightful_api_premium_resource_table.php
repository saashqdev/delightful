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
        // tableexistsinthennotexecute
        if (Schema::hasTable('delightful_api_premium_resources')) {
            return;
        }
        Schema::create('delightful_api_premium_resources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('endpoint_id', 64)->comment('accesspointID');
            $table->string('resource_name', 64)->comment('resourcename');
            $table->integer('billing_cycle_value')->default(0)->comment('billingperiodvalue');
            $table->tinyInteger('billing_cycle_type')->default(0)->comment('0: totalquantity, 1:second, 2:minuteseconds, 3:hour, 4:day');
            $table->integer('total_usage')->default(0)->comment('totalquantity');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['endpoint_id', 'id'], 'index_endpoint_id');
            $table->comment('APIresourcebillingruletable,supporttotalquantityorspeedratebilling');
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
