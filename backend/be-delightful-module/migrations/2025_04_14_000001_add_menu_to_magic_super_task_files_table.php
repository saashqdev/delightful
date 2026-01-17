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
        Schema::table('delightful_be_agent_task_files', function (Blueprint $table) {
            // 添加 attachments 字段，放在 tool 字段后面
            $table->string('menu', 255)->nullable()->comment('菜单信息');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_be_agent_task_files', function (Blueprint $table) {
            // 回滚时移除 attachments 字段
            $table->dropColumn('menu');
        });
    }
};
