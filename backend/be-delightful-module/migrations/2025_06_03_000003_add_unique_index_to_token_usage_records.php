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
        if (! Schema::hasTable('delightful_super_agent_token_usage_records')) {
            return;
        }

        Schema::table('delightful_super_agent_token_usage_records', function (Blueprint $table) {
            // Check if unique index exists before adding
            if (! Schema::hasIndex('delightful_super_agent_token_usage_records', 'idx_token_usage_unique')) {
                // Add unique composite index for idempotency
                $table->unique(['topic_id', 'task_id', 'sandbox_id', 'model_id'], 'idx_token_usage_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('delightful_super_agent_token_usage_records')) {
            return;
        }

        Schema::table('delightful_super_agent_token_usage_records', function (Blueprint $table) {
            // Check if unique index exists before dropping
            if (Schema::hasIndex('delightful_super_agent_token_usage_records', 'idx_token_usage_unique')) {
                // Drop the unique index
                $table->dropUnique('idx_token_usage_unique');
            }
        });
    }
};
