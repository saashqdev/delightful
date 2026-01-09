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
        Schema::table('delightful_api_msg_logs', function (Blueprint $table) {
            $table->integer('prompt_tokens')->default(0)->comment('提示词token数');
            $table->integer('completion_tokens')->default(0)->comment('完成内容token数');
            $table->integer('cache_write_tokens')->default(0)->comment('写入cache的token数');
            $table->integer('cache_read_tokens')->default(0)->comment('从cache读取的token数');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_api_msg_logs', function (Blueprint $table) {
            $table->dropColumn([
                'prompt_tokens',
                'completion_tokens',
                'cache_write_tokens',
                'cache_read_tokens',
            ]);
        });
    }
};
