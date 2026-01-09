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
        // judgetablewhether存in
        if (Schema::hasTable('delightful_chat_conversations')) {
            return;
        }
        Schema::create('delightful_chat_conversations', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 64)->comment('userid。thissessionwindow属attheuser。');
            $table->string('user_organization_code', 64)->comment('userorganizationencoding');
            // 收itempersonorganizationencoding
            $table->tinyInteger('receive_type')->comment('sessiontype。1：private chat，2：group chat，3：systemmessage，4：云document，5：多维table格 6：话题 7：applicationmessage');
            $table->string('receive_id', '64')->comment('session另one方id。differentconversation type，idimplicationdifferent。');
            $table->string('receive_organization_code', 64)->comment('收itempersonorganizationencoding');
            // whether免打扰
            $table->tinyInteger('is_not_disturb')->default(0)->comment('whether免打扰 0否 1is');
            // whether置top
            $table->tinyInteger('is_top')->default(0)->comment('whether置top 0否 1is');
            // whethermark
            $table->tinyInteger('is_mark')->default(0)->comment('whethermark 0否 1is');
            // status
            $table->tinyInteger('status')->default(0)->comment('sessionstatus。0:normal 1:notdisplay 2：delete');
            // current话题 id
            $table->string('current_topic_id', 64)->comment('current话题id')->nullable()->default('');
            // customizefield
            $table->text('extra')->comment('customizefield')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'receive_id', 'receive_type', 'user_organization_code', 'receive_organization_code'], 'unq_user_conversation');
            $table->comment('usersessionlist。sessionmaybeisprivate chat、group chat、systemmessage、one云documentor者多维table格etc。');
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
