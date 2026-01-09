<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_provider_models', function (Blueprint $table) {
            $table->json('visible_organizations')->default(Db::raw('(JSON_ARRAY())'))->comment('modelvisibleorganizationencoding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_provider_models', function (Blueprint $table) {
            $table->dropColumn('visible_organizations');
        });
    }
};
