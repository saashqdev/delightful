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
     * 运行迁移.
     */
    public function up(): void
    {
        // 为 delightful_be_agent_topics 表增加 topic_mode 字段
        Schema::table('delightful_be_agent_topics', function (Blueprint $table) {
            $table->string('topic_mode', 50)->default('general')->comment('话题模式: general-通用模式, ppt-PPT模式, data_analysis-数据分析模式, report-研报模式, meeting-会议模式, summary-总结模式, be_delightful-超级麦吉模式')->after('current_task_status');
        });

        echo '为话题表添加话题模式字段完成' . PHP_EOL;
    }

    /**
     * 回滚迁移.
     */
    public function down(): void
    {
        // 删除 delightful_be_agent_topics 表的 topic_mode 字段
        Schema::table('delightful_be_agent_topics', function (Blueprint $table) {
            $table->dropColumn('topic_mode');
        });

        echo '删除话题表的话题模式字段完成' . PHP_EOL;
    }
};
