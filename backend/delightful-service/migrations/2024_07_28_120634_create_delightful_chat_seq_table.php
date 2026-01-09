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
        if (Schema::hasTable('delightful_chat_sequences')) {
            return;
        }
        Schema::create('delightful_chat_sequences', static function (Blueprint $table) {
            // according to上面的建table语句，得出以下代码
            $table->bigIncrements('id')->comment('primary keyid,没啥用');
            $table->string('organization_code', 64)->comment('序列号所属的organization编码.')->default('');
            $table->tinyInteger('object_type')->comment('objecttype,0:ai,1:user；2：应用;3:文档;4:多维table格');
            $table->string('object_id', 64)->comment('objectid. 如果是user时,table示delightful_id');
            $table->string('seq_id', 64)->comment('message序列号 id，每个账号的所有message必须逐渐增大');
            $table->string('seq_type', 32)->comment('message大type:控制message,chatmessage。');
            $table->text('content')->comment('序列号详情. 一些不可见的控制message,只在seqtable存在详情. 以及写时复制一份messagetablecontent到seqtable用.');
            $table->string('delightful_message_id', 64)->comment('service端生成的唯一messageid,用于messagewithdraw/edit');
            $table->string('message_id', 64)->comment('序列号关联的usermessageid,implement已读回执,messagewithdraw/edit等')->default(0);
            // quote的messageid
            $table->string('refer_message_id', 64)->comment('quote的messageid,implement已读回执,messagewithdraw/edit等');
            // sender_message_id
            $table->string('sender_message_id', 64)->comment('发送方的messageid,用于messagewithdraw/edit');
            // sessionid
            $table->string('conversation_id', 64)->comment('message所属sessionid,冗余field');
            $table->tinyInteger('status')->default(0)->comment('messagestatus,0:unread, 1:seen, 2:read, 3:revoked');
            // message接收人list
            $table->text('receive_list')->comment('message接收人list,全量record未读/已读/已查看userlist');
            $table->text('extra')->comment('附加field，record一些extensionproperty。 such as话题id。');
            // app_message_id
            $table->string('app_message_id', 64)->default('')->comment('冗余field,客户端生成的messageid,用于防客户端重复');
            # 以下是索引set
            // delightful_message_id 索引
            $table->index(['delightful_message_id'], 'idx_delightful_message_id');
            // 因为经常需要按 seq_id sort，所以增加联合索引
            // 以下索引create移动到单独的迁移file中
            $table->timestamps();
            $table->softDeletes();
            $table->comment('账号收件箱的序列号table,每个账号的所有message必须单调递增');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_chat_sequences');
    }
};
