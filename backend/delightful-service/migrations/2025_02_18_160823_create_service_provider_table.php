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
        if (Schema::hasTable('service_provider')) {
            return;
        }

        Schema::create('service_provider', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->comment('服务商name');
            $table->string('provider_code', 50)->comment('服务商encoding，表示属于哪个 AI 服务商。如：官方，DS，阿里云等');
            $table->string('description', 255)->nullable()->comment('服务商description');
            $table->string('icon', 255)->nullable()->comment('服务商图标');
            $table->tinyInteger('provider_type')->default(0)->comment('服务商type：0-普通，1-官方');
            $table->string('category', 20)->comment('category：llm-大model，vlm-视觉model');
            $table->tinyInteger('status')->default(0)->comment('status：0-未enable，1-enable');
            $table->tinyInteger('is_models_enable')->default(0)->comment('model列表get：0-未enable，1-enable');
            $table->timestamps();
            $table->softDeletes();
            $table->index('category', 'idx_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider');
    }
};
