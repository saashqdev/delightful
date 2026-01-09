<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateDelightfulChatMessageTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('delightful_chat_messages')) {
            return;
        }
        Schema::create('delightful_chat_messages', static function (Blueprint $table) {
            // according to上面的建table语句，得出以下代码
            $table->bigIncrements('id');
            // 发件方所属organization
            $table->string('sender_id', 64)->comment('发件方的id');
            $table->tinyInteger('sender_type')->comment('发件方usertype,1:user(ai也被认为是user)；2：应用;3:文档;4:多维table格');
            $table->string('sender_organization_code', 64)->comment('发件方organization编码,可能为空string')->default('');
            // 接收方所属organization
            $table->string('receive_id', 64)->comment('接收方id，可能是人类、ai或者应用/文档/多维table格等');
            $table->tinyInteger('receive_type')->comment('接收方type,1:user(ai也被认为是user)；2：应用;3:文档;4:多维table格');
            $table->string('receive_organization_code', 64)->comment('接收方organization编码,可能为空string')->default('');
            // message的相关id
            $table->string('app_message_id', 64)->comment('客户端生成的messageid,用于防客户端重复');
            $table->string('delightful_message_id', 64)->comment('service端生成的唯一messageid,用于message撤回/编辑');
            # ## message结构
            // message优先级,由于系统稳定性管理
            $table->tinyInteger('priority')->default(0)->comment('message优先级,0~255,0最低,255最高');
            $table->string('message_type', 32)->comment('messagetype:文本/table情/文件/markdown等');
            $table->text('content')->comment('message详情');
            $table->timestamp('send_time')->comment('message发送time');
            $table->index(['sender_id', 'sender_type', 'sender_organization_code'], 'idx_sender_id_type');
            $table->index(['receive_id', 'receive_type', 'receive_organization_code'], 'idx_receive_id_type');
            $table->unique(['delightful_message_id'], 'unq_delightful_message_id');
            $table->timestamps();
            $table->comment('message详情table,record一条message的根本info');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_chat_messages');
    }
}
