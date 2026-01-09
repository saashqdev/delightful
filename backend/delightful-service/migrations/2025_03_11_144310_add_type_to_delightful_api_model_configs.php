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
        Schema::table('delightful_api_model_configs', function (Blueprint $table) {
            $table->string('type', 80)->default('')->comment('modeltype')->after('model');
            // give model increasecomment：actualup代table endpoint
            $table->string('model')->comment('actualup代table endpoint')->change();
            $table->index('type', 'idx_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_api_model_configs', function (Blueprint $table) {
            $table->dropIndex('idx_type');
            $table->dropColumn('type');
            $table->string('model')->comment('modelname')->change();
        });
    }
};
