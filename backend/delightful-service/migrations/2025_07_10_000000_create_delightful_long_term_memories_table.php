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
        Schema::create('delightful_long_term_memories', function (Blueprint $table) {
            $table->string('id', 36)->primary()->comment('记忆唯一ID');
            $table->text('content')->comment('记忆content');
            $table->text('pending_content')->nullable()->comment('待变更的记忆content，等待user接受变更');
            $table->text('explanation')->nullable()->comment('记忆解释，instruction这条记忆为什么value得record');
            $table->text('origin_text')->nullable()->comment('original文本content');
            $table->string('memory_type', 50)->default('manual_input')->comment('记忆type');
            $table->string('status', 20)->default('pending')->comment('记忆status：pending-待接受, active-已生效, pending_revision-待修订');
            $table->tinyInteger('enabled')->default(0)->comment('是否enable：0-disable，1-enable（仅 active status的记忆canset）');
            $table->decimal('confidence', 3, 2)->unsigned()->default(0.8)->comment('置信度(0-1)');
            $table->decimal('importance', 3, 2)->unsigned()->default(0.5)->comment('重要性(0-1)');
            $table->unsignedInteger('access_count')->default(0)->comment('access次数');
            $table->unsignedInteger('reinforcement_count')->default(0)->comment('强化次数');
            $table->decimal('decay_factor', 3, 2)->unsigned()->default(1.0)->comment('衰减因子(0-1)');
            $table->json('tags')->nullable()->comment('taglist');
            $table->json('metadata')->nullable()->comment('元data');
            $table->string('org_id', 36)->comment('organizationID');
            $table->string('app_id', 36)->comment('applicationID');
            $table->string('project_id', 36)->nullable()->default(null)->comment('projectID');
            $table->string('user_id', 36)->comment('userID');
            $table->timestamp('last_accessed_at')->nullable()->comment('最后accesstime');
            $table->timestamp('last_reinforced_at')->nullable()->comment('最后强化time');
            $table->timestamp('expires_at')->nullable()->comment('expiretime');
            $table->timestamp('created_at')->useCurrent()->comment('createtime');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('updatetime');
            $table->softDeletes()->comment('deletetime');
            // 索引
            $table->index(['org_id', 'app_id', 'user_id', 'project_id', 'last_accessed_at'], 'idx_user_last_accessed');
            $table->index(['org_id', 'app_id', 'user_id', 'project_id', 'importance'], 'idx_user_importance');
            $table->index(['expires_at', 'deleted_at'], 'idx_expires_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_long_term_memories');
    }
};
