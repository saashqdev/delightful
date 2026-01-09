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
            // according toupsurface建table语sentence,outbydowncode
            $table->bigIncrements('id')->comment('primary keyid,not啥use');
            $table->string('organization_code', 64)->comment('序columnnumberbelong toorganizationencoding.')->default('');
            $table->tinyInteger('object_type')->comment('objecttype,0:ai,1:user;2:application;3:document;4:multi-dimensionaltable格');
            $table->string('object_id', 64)->comment('objectid. ifisusero clock,table示delightful_id');
            $table->string('seq_id', 64)->comment('message序columnnumber id,each账number所havemessagemustgradually增big');
            $table->string('seq_type', 32)->comment('messagebigtype:controlmessage,chatmessage.');
            $table->text('content')->comment('序columnnumberdetail. onethesenotvisiblecontrolmessage,onlyinseqtable存indetail. byand写o clockcopyonesharemessagetablecontenttoseqtableuse.');
            $table->string('delightful_message_id', 64)->comment('service端generate唯onemessageid,useatmessagewithdraw/edit');
            $table->string('message_id', 64)->comment('序columnnumberassociateusermessageid,implementalready读return执,messagewithdraw/editetc')->default(0);
            // quotemessageid
            $table->string('refer_message_id', 64)->comment('quotemessageid,implementalready读return执,messagewithdraw/editetc');
            // sender_message_id
            $table->string('sender_message_id', 64)->comment('send方messageid,useatmessagewithdraw/edit');
            // sessionid
            $table->string('conversation_id', 64)->comment('messagebelong tosessionid,冗remainderfield');
            $table->tinyInteger('status')->default(0)->comment('messagestatus,0:unread, 1:seen, 2:read, 3:revoked');
            // messagereceivepersonlist
            $table->text('receive_list')->comment('messagereceivepersonlist,allquantityrecordnot读/already读/alreadyviewuserlist');
            $table->text('extra')->comment('attachaddfield,recordonetheseextensionproperty. such astopicid.');
            // app_message_id
            $table->string('app_message_id', 64)->default('')->comment('冗remainderfield,customer端generatemessageid,useat防customer端duplicate');
            # bydownisindexset
            // delightful_message_id index
            $table->index(['delightful_message_id'], 'idx_delightful_message_id');
            // 因foroftenneed按 seq_id sort,所byincreaseunionindex
            // bydownindexcreatemovetosingle独migratefilemiddle
            $table->timestamps();
            $table->softDeletes();
            $table->comment('账number收item箱序columnnumbertable,each账number所havemessagemustsingleincrement');
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
