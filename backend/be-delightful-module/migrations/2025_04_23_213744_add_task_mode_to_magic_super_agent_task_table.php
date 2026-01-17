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
        Schema::table('delightful_be_agent_task', function (Blueprint $table) {
            if (! Schema::hasColumn('delightful_be_agent_task', 'task_mode')) {
                $table->string('task_mode', 50)->default('chat')->comment('聊天模式: chat-聊天模式, plan-规划模式');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_be_agent_task', function (Blueprint $table) {
            if (Schema::hasColumn('delightful_be_agent_task', 'task_mode')) {
                $table->dropColumn('task_mode');
            }
        });
    }
};
