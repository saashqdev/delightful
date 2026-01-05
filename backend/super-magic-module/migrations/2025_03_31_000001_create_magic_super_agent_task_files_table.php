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
        if (Schema::hasTable('magic_super_agent_task_files')) {
            return;
        }
        Schema::create('magic_super_agent_task_files', static function (Blueprint $table) {
            $table->bigIncrements('file_id')->comment('primary key');
            $table->string('user_id', 128)->comment('user_id');
            $table->string('organization_code', 64)->comment('organization encoding');
            $table->bigInteger('topic_id')->comment('ID of magic_general_agent_topics');
            $table->bigInteger('task_id')->comment('ID of magic_general_agent_task');
            $table->string('file_type', 32)->default('')->comment('file type');
            $table->string('file_name', 256)->default('')->comment('file name');
            $table->string('file_extension', 32)->default('')->comment('file extension');
            $table->string('file_key', 255)->default('')->comment('file storage path');
            $table->unsignedBigInteger('file_size')->default(0)->comment('file size');
            $table->string('external_url', 1024)->default('')->comment('external link address');
            $table->softDeletes();
            $table->timestamps();

            // index
            $table->index('topic_id', 'idx_topic_id');
            $table->index('task_id', 'idx_task_id');
            $table->index('user_id', 'idx_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_general_agent_task_files');
    }
};
