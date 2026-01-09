<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class DelightfulFlowKnowledgeAddRetrieveConfig extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('delightful_flow_knowledge', function (Blueprint $table) {
            $table->string('retrieve_config', 2000)->nullable()->comment('retrieveconfiguration');
        });

        // notsettingdefaultconfiguration，letfield保持for null
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_flow_knowledge', function (Blueprint $table) {
            $table->dropColumn('retrieve_config');
        });
    }
}
