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
            $table->unsignedBigInteger('service_provider_config_id')->index()->comment('servicequotientID');
            $table->string('name', 50)->comment('modelname');
            $table->string('model_version', 50)->comment('modelinservicequotientdownname');
            $table->string('model_id', 50)->comment('modeltrueactualID');
            $table->string('category')->comment('modelcategory:llm/vlm');
            $table->tinyInteger('model_type')->comment('specifictype,useatminutegroupuse');
            $table->json('config')->comment('modelconfigurationinformation');
            $table->string('description', 255)->nullable()->comment('modeldescription');
            $table->integer('sort')->default(0)->comment('sort');
            $table->string('icon')->default('')->comment('graphmark');
            $table->string('organization_code')->comment('organizationencoding');
            $table->tinyInteger('status')->default(0)->comment('status:0-notenable,1-enable');
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
