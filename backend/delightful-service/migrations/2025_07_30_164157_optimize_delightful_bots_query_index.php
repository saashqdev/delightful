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
     * optimize delightful_bots and delightful_bot_versions 表多query场景performance
     * add多复合indexsupportdifferentquerymode.
     */
    public function up(): void
    {
        // optimize delightful_bots 表
        Schema::table('delightful_bots', function (Blueprint $table) {
            // 1. optimize chat-mode/available interface JOIN query
            // to应SQL: delightful_bots.bot_version_id = delightful_bot_versions.id AND delightful_bots.status = '7'
            $table->index(['bot_version_id', 'status'], 'idx_bot_version_status');

            // 2. optimize企业助理query (queriesAgentsmethod)
            // to应SQL: WHERE organization_code = ? AND status = ?
            $table->index(['organization_code', 'status'], 'idx_organization_status');
        });

        // optimize delightful_bot_versions 表
        Schema::table('delightful_bot_versions', function (Blueprint $table) {
            // 先deletealready存insinglefieldindex，avoidindex冗remainder
            if (Schema::hasIndex('delightful_bot_versions', 'delightful_bot_versions_organization_code_index')) {
                $table->dropIndex('delightful_bot_versions_organization_code_index');
            }

            // 3. optimize企业publishstatusquery
            // to应SQL: WHERE organization_code = ? AND enterprise_release_status = ?
            $table->index(['organization_code', 'enterprise_release_status'], 'idx_organization_enterprise_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
