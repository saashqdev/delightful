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
            // according toupsurfacebuildtablelanguagesentence,outbydowncode
            $table->bigIncrements('id')->comment('primary keyid,notwhatuse');
            $table->string('organization_code', 64)->comment('sequencecolumnnumberbelong toorganizationencoding.')->default('');
            $table->tinyInteger('object_type')->comment('objecttype,0:ai,1:user;2:application;3:document;4:multi-dimensionaltableformat');
            $table->string('object_id', 64)->comment('objectid. ifisusero clock,tableshowdelightful_id');
            $table->string('seq_id', 64)->comment('messagesequencecolumnnumber id,eachaccountnumber havemessagemustgraduallyincreasebig');
            $table->string('seq_type', 32)->comment('messagebigtype:controlmessage,chatmessage.');
            $table->text('content')->comment('sequencecolumnnumberdetail. onethesenotvisiblecontrolmessage,onlyinseqtableexistsindetail. byandwriteo clockcopyonesharemessagetablecontenttoseqtableuse.');
            $table->string('delightful_message_id', 64)->comment('serviceclientgenerateuniqueonemessageid,useatmessagewithdraw/edit');
            $table->string('message_id', 64)->comment('sequencecolumnnumberassociateusermessageid,implementalreadyreadreturnexecute,messagewithdraw/editetc')->default(0);
            // quotemessageid
            $table->string('refer_message_id', 64)->comment('quotemessageid,implementalreadyreadreturnexecute,messagewithdraw/editetc');
            // sender_message_id
            $table->string('sender_message_id', 64)->comment('sendsidemessageid,useatmessagewithdraw/edit');
            // sessionid
            $table->string('conversation_id', 64)->comment('messagebelong tosessionid,redundantremainderfield');
            $table->tinyInteger('status')->default(0)->comment('messagestatus,0:unread, 1:seen, 2:read, 3:revoked');
            // messagereceivepersonlist
            $table->text('receive_list')->comment('messagereceivepersonlist,allquantityrecordnotread/alreadyread/alreadyviewuserlist');
            $table->text('extra')->comment('attachaddfield,recordonetheseextensionproperty. such astopicid.');
            // app_message_id
            $table->string('app_message_id', 64)->default('')->comment('redundantremainderfield,customerclientgeneratemessageid,useatpreventcustomerclientduplicate');
            # bydownisindexset
            // delightful_message_id index
            $table->index(['delightful_message_id'], 'idx_delightful_message_id');
            // factorforoftenneedby seq_id sort, byincreaseunionindex
            // bydownindexcreatemovetosingleuniquemigratefilemiddle
            $table->timestamps();
            $table->softDeletes();
            $table->comment('accountnumberreceiveitembox sequencecolumnnumbertable,eachaccountnumber havemessagemustsingleincrement');
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
