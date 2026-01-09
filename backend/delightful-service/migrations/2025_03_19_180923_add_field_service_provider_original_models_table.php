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
        Schema::table('service_provider_original_models', function (Blueprint $table) {
            // addtype，systemdefault的，自己add的
            $table->tinyInteger('type')->default(0)->comment('type，0：systemdefault，1：自己add');
            // organizationencoding
            $table->string('organization_code')->default('')->comment('organizationencoding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_provider_original_models', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('organization_code');
        });
    }
};
