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
        if (Schema::hasTable('service_provider_models')) {
            return;
        }

        Schema::create('service_provider_models', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('service_provider_config_id')->index()->comment('服务商ID');
            $table->string('name', 50)->comment('modelname');
            $table->string('model_version', 50)->comment('model在服务商下的name');
            $table->string('model_id', 50)->comment('modeltrue实ID');
            $table->string('category')->comment('modelcategory：llm/vlm');
            $table->tinyInteger('model_type')->comment('具体type,用于分组用');
            $table->json('config')->comment('model的configurationinformation');
            $table->string('description', 255)->nullable()->comment('modeldescription');
            $table->integer('sort')->default(0)->comment('sort');
            $table->string('icon')->default('')->comment('图标');
            $table->string('organization_code')->comment('organizationencoding');
            $table->tinyInteger('status')->default(0)->comment('status：0-未enable，1-enable');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_code', 'service_provider_config_id'], 'idx_organization_code_service_provider_config_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_models');
    }
};
