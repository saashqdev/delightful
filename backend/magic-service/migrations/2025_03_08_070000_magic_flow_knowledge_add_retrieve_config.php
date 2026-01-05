<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class MagicFlowKnowledgeAddRetrieveConfig extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('magic_flow_knowledge', function (Blueprint $table) {
            $table->string('retrieve_config', 2000)->nullable()->comment('检索配置');
        });

        // 不设置默认配置，让字段保持为 null
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('magic_flow_knowledge', function (Blueprint $table) {
            $table->dropColumn('retrieve_config');
        });
    }
}
