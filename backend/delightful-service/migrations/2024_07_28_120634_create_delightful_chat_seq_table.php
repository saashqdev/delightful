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
            // according toupsurface的建table语sentence，得出bydowncode
            $table->bigIncrements('id')->comment('primary keyid,not啥use');
            $table->string('organization_code', 64)->comment('序columnnumber所属的organizationencoding.')->default('');
            $table->tinyInteger('object_type')->comment('objecttype,0:ai,1:user；2：application;3:document;4:多维table格');
            $table->string('object_id', 64)->comment('objectid. if是usero clock,table示delightful_id');
            $table->string('seq_id', 64)->comment('message序columnnumber id，each账number的所havemessagemust逐渐增大');
            $table->string('seq_type', 32)->comment('message大type:控制message,chatmessage。');
            $table->text('content')->comment('序columnnumberdetail. 一些not可见的控制message,只inseqtable存indetail. by及写o clock复制一sharemessagetablecontenttoseqtableuse.');
            $table->string('delightful_message_id', 64)->comment('service端generate的唯一messageid,useatmessagewithdraw/edit');
            $table->string('message_id', 64)->comment('序columnnumberassociate的usermessageid,implement已读回执,messagewithdraw/editetc')->default(0);
            // quote的messageid
            $table->string('refer_message_id', 64)->comment('quote的messageid,implement已读回执,messagewithdraw/editetc');
            // sender_message_id
            $table->string('sender_message_id', 64)->comment('send方的messageid,useatmessagewithdraw/edit');
            // sessionid
            $table->string('conversation_id', 64)->comment('message所属sessionid,冗余field');
            $table->tinyInteger('status')->default(0)->comment('messagestatus,0:unread, 1:seen, 2:read, 3:revoked');
            // messagereceive人list
            $table->text('receive_list')->comment('messagereceive人list,allquantityrecord未读/已读/已查看userlist');
            $table->text('extra')->comment('attach加field，record一些extensionproperty。 such as话题id。');
            // app_message_id
            $table->string('app_message_id', 64)->default('')->comment('冗余field,客户端generate的messageid,useat防客户端重复');
            # bydown是索引set
            // delightful_message_id 索引
            $table->index(['delightful_message_id'], 'idx_delightful_message_id');
            // 因为经常need按 seq_id sort，所by增加联合索引
            // bydown索引create移动to单独的迁移filemiddle
            $table->timestamps();
            $table->softDeletes();
            $table->comment('账number收item箱的序columnnumbertable,each账number的所havemessagemust单调递增');
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
