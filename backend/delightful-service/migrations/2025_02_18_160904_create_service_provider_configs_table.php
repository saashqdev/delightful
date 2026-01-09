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
        if (Schema::hasTable('service_provider_configs')) {
            return;
        }

        Schema::create('service_provider_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('service_provider_id')->comment('service商ID');
            $table->string('organization_code', 50)->comment('organizationencoding');
            $table->longText('config')->nullable()->comment('configurationinformationJSON');
            $table->tinyInteger('status')->default(0)->comment('status：0-未enable，1-enable');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_code', 'status'], 'index_service_provider_configs_organization_code_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider_configs');
    }
};
