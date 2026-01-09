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
        Schema::table('delightful_flow_knowledge', function (Blueprint $table) {
            // checkwhetheralready存infield,avoidduplicateadd
            if (! Schema::hasColumn('delightful_flow_knowledge', 'source_type')) {
                $table->integer('source_type')->nullable()->comment('data源type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_flow_knowledge', function (Blueprint $table) {
            // checkwhetheralready存infield,avoidduplicatedelete
            if (Schema::hasColumn('delightful_flow_knowledge', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};
