<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddCostToDelightfulBeAgentTopicsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('delightful_be_agent_topics', function (Blueprint $table) {
            $table->decimal('cost', 10, 4)->default(0.0000)->comment('Topic cost amount')->after('task_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_be_agent_topics', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
}
