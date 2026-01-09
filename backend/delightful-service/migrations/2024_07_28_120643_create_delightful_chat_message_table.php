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
            // according toupsurface建table语sentence，outbydowncode
            $table->bigIncrements('id');
            // hairitem方所属organization
            $table->string('sender_id', 64)->comment('hairitem方id');
            $table->tinyInteger('sender_type')->comment('hairitem方usertype,1:user(aialsobe认forisuser)；2：application;3:document;4:多维table格');
            $table->string('sender_organization_code', 64)->comment('hairitem方organizationencoding,maybeforemptystring')->default('');
            // receive方所属organization
            $table->string('receive_id', 64)->comment('receive方id，maybeispersoncategory、aior者application/document/多维table格etc');
            $table->tinyInteger('receive_type')->comment('receive方type,1:user(aialsobe认forisuser)；2：application;3:document;4:多维table格');
            $table->string('receive_organization_code', 64)->comment('receive方organizationencoding,maybeforemptystring')->default('');
            // message相closeid
            $table->string('app_message_id', 64)->comment('customer端generatemessageid,useat防customer端duplicate');
            $table->string('delightful_message_id', 64)->comment('service端generate唯onemessageid,useatmessagewithdraw/edit');
            # ## message结构
            // message优先level,byatsystemstablepropertymanage
            $table->tinyInteger('priority')->default(0)->comment('message优先level,0~255,0most低,255most高');
            $table->string('message_type', 32)->comment('messagetype:text/table情/file/markdownetc');
            $table->text('content')->comment('messagedetail');
            $table->timestamp('send_time')->comment('messagesendtime');
            $table->index(['sender_id', 'sender_type', 'sender_organization_code'], 'idx_sender_id_type');
            $table->index(['receive_id', 'receive_type', 'receive_organization_code'], 'idx_receive_id_type');
            $table->unique(['delightful_message_id'], 'unq_delightful_message_id');
            $table->timestamps();
            $table->comment('messagedetailtable,recordoneitemmessageroot本info');
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
