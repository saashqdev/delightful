<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

class CreateDelightfulApiModelConfigTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delightful_api_model_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('model')->comment('model');
            $table->unsignedDecimal('total_amount', 40, 6)->comment('totalquota');
            $table->unsignedDecimal('use_amount', 40, 6)->comment('usequota')->default(0);
            $table->integer('rpm')->comment('limitstream');
            $table->unsignedDecimal('exchange_rate')->comment('gatherrate');
            $table->unsignedDecimal('input_cost_per_1000', 40, 6)->comment('1000 token input feeuse');
            $table->unsignedDecimal('output_cost_per_1000', 40, 6)->comment('1000 token inputoutfeeuse');
            $table->timestamp('created_at')->default(Db::raw('CURRENT_TIMESTAMP'))->comment('creation time');
            $table->timestamp('updated_at')->default(Db::raw('CURRENT_TIMESTAMP'))->comment('modification time')->nullable();
            $table->timestamp('deleted_at')->comment('logicdelete')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_model_config');
    }
}
