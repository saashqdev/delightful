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
        Schema::table('service_provider_models', function (Blueprint $table) {
            // 添加 model_parent_id 和 status 联合索引
            $table->index(['model_parent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_provider_models', function (Blueprint $table) {
            // delete model_parent_id 和 status 联合索引
            $table->dropIndex(['model_parent_id', 'status']);
        });
    }
};
