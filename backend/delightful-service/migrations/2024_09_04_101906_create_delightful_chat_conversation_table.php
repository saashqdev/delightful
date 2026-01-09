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
        // 判断table是否存在
        if (Schema::hasTable('delightful_chat_conversations')) {
            return;
        }
        Schema::create('delightful_chat_conversations', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 64)->comment('userid。此session窗口属于该user。');
            $table->string('user_organization_code', 64)->comment('userorganizationencoding');
            // 收件人organizationencoding
            $table->tinyInteger('receive_type')->comment('sessiontype。1：private chat，2：group chat，3：系统message，4：云document，5：多维table格 6：话题 7：applicationmessage');
            $table->string('receive_id', '64')->comment('session另一方的id。different的conversation type，id含义different。');
            $table->string('receive_organization_code', 64)->comment('收件人organizationencoding');
            // 是否免打扰
            $table->tinyInteger('is_not_disturb')->default(0)->comment('是否免打扰 0否 1是');
            // 是否置顶
            $table->tinyInteger('is_top')->default(0)->comment('是否置顶 0否 1是');
            // 是否mark
            $table->tinyInteger('is_mark')->default(0)->comment('是否mark 0否 1是');
            // status
            $table->tinyInteger('status')->default(0)->comment('sessionstatus。0:正常 1:不显示 2：delete');
            // current话题 id
            $table->string('current_topic_id', 64)->comment('current话题id')->nullable()->default('');
            // customizefield
            $table->text('extra')->comment('customizefield')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'receive_id', 'receive_type', 'user_organization_code', 'receive_organization_code'], 'unq_user_conversation');
            $table->comment('user的sessionlist。session可能是private chat、group chat、系统message、一个云document或者多维table格等。');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_chat_conversations');
    }
};
