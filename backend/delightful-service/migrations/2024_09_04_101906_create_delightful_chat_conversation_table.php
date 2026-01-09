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
        // judgetablewhetherexistsin
        if (Schema::hasTable('delightful_chat_conversations')) {
            return;
        }
        Schema::create('delightful_chat_conversations', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 64)->comment('userid.thissessionwindowbelongattheuser.');
            $table->string('user_organization_code', 64)->comment('userorganizationencoding');
            // receiveitempersonorganizationencoding
            $table->tinyInteger('receive_type')->comment('sessiontype.1:private chat,2:group chat,3:systemmessage,4:clouddocument,5:multi-dimensionaltableformat 6:topic 7:applicationmessage');
            $table->string('receive_id', '64')->comment('sessionanotheronesideid.differentconversation type,idimplicationdifferent.');
            $table->string('receive_organization_code', 64)->comment('receiveitempersonorganizationencoding');
            // whetherdo not disturb
            $table->tinyInteger('is_not_disturb')->default(0)->comment('whetherdo not disturb 0no 1is');
            // whethersettop
            $table->tinyInteger('is_top')->default(0)->comment('whethersettop 0no 1is');
            // whethermark
            $table->tinyInteger('is_mark')->default(0)->comment('whethermark 0no 1is');
            // status
            $table->tinyInteger('status')->default(0)->comment('sessionstatus.0:normal 1:notdisplay 2:delete');
            // currenttopic id
            $table->string('current_topic_id', 64)->comment('currenttopicid')->nullable()->default('');
            // customizefield
            $table->text('extra')->comment('customizefield')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'receive_id', 'receive_type', 'user_organization_code', 'receive_organization_code'], 'unq_user_conversation');
            $table->comment('usersessionlist.sessionmaybeisprivate chat,group chat,systemmessage,oneclouddocumentorpersonmulti-dimensionaltableformatetc.');
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
