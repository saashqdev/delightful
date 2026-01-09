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
        Schema::table('delightful_environments', function (Blueprint $table) {
            // environment_code
            $table->string('environment_code', 64)->comment('environment code')->default('');
            $table->string('third_platform_type', 64)->comment('thethird-party平台type')->default('');
            // index,theoreticalup唯one，but业务need，notpass mysql 唯oneindexcome约束
            $table->index(['environment_code', 'third_platform_type'], 'idx_environment_code_third_platform_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
