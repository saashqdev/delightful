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
        Schema::table('delightful_bot_versions', function (Blueprint $table) {
            $table->json('visibility_config')->nullable()->comment('可见性配置，包含可见范围类型和可见成员/department列table');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_bot_versions', function (Blueprint $table) {
            $table->dropColumn('visibility_config');
        });
    }
};
