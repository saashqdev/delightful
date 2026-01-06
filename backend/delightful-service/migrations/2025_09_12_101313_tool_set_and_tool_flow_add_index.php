<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db as DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE INDEX idx_delightful_flows_query_optimal ON delightful_flows (organization_code(50), type, enabled, tool_set_id(40), updated_at)');

        // Add optimal index for delightful_flow_tool_sets table
        Schema::table('delightful_flow_tool_sets', function (Blueprint $table) {
            $table->index([
                'organization_code',
                'enabled',
                'updated_at',
            ], 'idx_delightful_flow_tool_sets_query_optimal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove optimal index for delightful_flows table
        DB::statement('DROP INDEX idx_delightful_flows_query_optimal ON delightful_flows');

        // Remove optimal index for delightful_flow_tool_sets table
        Schema::table('delightful_flow_tool_sets', function (Blueprint $table) {
            $table->dropIndex('idx_delightful_flow_tool_sets_query_optimal');
        });
    }
};
