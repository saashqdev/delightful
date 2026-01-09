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
        if (Schema::hasTable('delightful_mode_group_relations')) {
            return;
        }

        Schema::create('delightful_mode_group_relations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('mode_id')->unsigned()->default(0)->comment('modeID');
            $table->bigInteger('group_id')->unsigned()->default(0)->comment('分组ID');
            $table->string('model_id')->default('')->comment('模型ID');
            $table->bigInteger('provider_model_id')->unsigned()->default(0)->comment('模型table主键 id');
            $table->integer('sort')->default(0)->comment('sort权重');
            $table->string('organization_code', 32)->default('')->comment('organization代码');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
