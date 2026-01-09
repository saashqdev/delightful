<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateDelightfulChatGroupTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // judgetablewhetherexistsin
        if (Schema::hasTable('delightful_chat_groups')) {
            return;
        }
        Schema::create('delightful_chat_groups', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('group_name', 64)->comment('groupname')->default('');
            $table->string('group_avatar', 255)->comment('groupavatar')->default('');
            $table->string('group_notice', 255)->comment('groupannouncement')->default('');
            $table->string('group_owner', 64)->comment('group owner');
            // groupbelong toorganization
            $table->string('organization_code', 64)->comment('grouporganizationencoding')->default('');
            $table->string('group_tag', 64)->comment('grouptag:0:notag,1:outsidedepartment group;2:insidedepartment group;3:allmember group')->default('0');
            $table->tinyInteger('group_type')->default(1)->comment('grouptype,1:conversation;2:topic');
            $table->tinyInteger('group_status')->default(1)->comment('groupstatus,1:normal;2:dissolve');
            // memberuplimit
            $table->integer('member_limit')->default(1000)->comment('groupmemberuplimit');
            $table->softDeletes();
            $table->timestamps();
            $table->comment('grouptable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_chat_groups');
    }
}
