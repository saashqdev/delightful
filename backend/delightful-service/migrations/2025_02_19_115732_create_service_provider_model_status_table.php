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
        if (Schema::hasTable('service_provider_model_status')) {
            return;
        }
        Schema::create('service_provider_model_status', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('model_id')->comment('modelid');
            $table->string('model_version')->comment('modelname');
            $table->string('organization_code')->comment('organizationencoding');
            $table->bigInteger('service_provider_config_id')->comment('to应servicequotientid');
            $table->tinyInteger('status')->default(0)->comment('status：0-notenable，1-enable');
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
        Schema::dropIfExists('service_provider_model_status');
    }
};
