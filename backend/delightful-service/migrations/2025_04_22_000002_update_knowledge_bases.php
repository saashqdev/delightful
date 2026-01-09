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
        // modify表结构，add新field
        Schema::table('delightful_flow_knowledge', function (Blueprint $table) {
            // checkwhetheralready存infield，避免duplicateadd
            if (! Schema::hasColumn('delightful_flow_knowledge', 'fragment_config')) {
                $table->string('fragment_config', 2000)->nullable()->comment('minutesegmentconfiguration');
            }
            if (! Schema::hasColumn('delightful_flow_knowledge', 'embedding_config')) {
                $table->string('embedding_config', 2000)->nullable()->comment('嵌入configuration');
            }
            if (! Schema::hasColumn('delightful_flow_knowledge', 'is_draft')) {
                $table->tinyInteger('is_draft')->default(0)->comment('whetherfordraft');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_flow_knowledge', function (Blueprint $table) {
            // checkwhetheralready存infield，避免duplicatedelete
            if (Schema::hasColumn('delightful_flow_knowledge', 'fragment_config')) {
                $table->dropColumn('fragment_config');
            }
            if (Schema::hasColumn('delightful_flow_knowledge', 'embedding_config')) {
                $table->dropColumn('embedding_config');
            }
            if (Schema::hasColumn('delightful_flow_knowledge', 'is_draft')) {
                $table->dropColumn('is_draft');
            }
        });
    }
};
