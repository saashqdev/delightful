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
        Schema::create('delightful_api_user_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 64)->default('')->comment('userid');
            $table->string('organization_code', 64)->default('')->comment('organizationcode');
            $table->string('app_code', 64)->default('')->comment('applicationcode');
            $table->unsignedDecimal('total_amount', 40, 6)->comment('总quota')->default(0);
            $table->unsignedDecimal('use_amount', 40, 6)->comment('usequota')->default(0);
            $table->unsignedInteger('rpm')->comment('RPM限stream')->default(0);
            $table->datetimes();
            $table->softDeletes();

            $table->index(['user_id', 'app_code', 'organization_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_user_configs');
    }
};
