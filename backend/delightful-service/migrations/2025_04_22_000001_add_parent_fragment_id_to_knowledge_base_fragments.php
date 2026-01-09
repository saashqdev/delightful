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
        // modify表结构，addnewfield
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            // checkwhetheralready存infield，avoidduplicateadd
            if (! Schema::hasColumn('delightful_flow_knowledge_fragment', 'parent_fragment_id')) {
                $table->unsignedBigInteger('parent_fragment_id')->nullable()->comment('父slicesegmentid')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            // checkwhetheralready存infield，avoidduplicatedelete
            if (Schema::hasColumn('delightful_flow_knowledge_fragment', 'parent_fragment_id')) {
                $table->dropColumn('parent_fragment_id');
            }
        });
    }
};
