<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateDelightfulChatFriendTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('delightful_chat_friends')) {
            return;
        }
        Schema::create('delightful_chat_friends', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 64)->comment('userid');
            // userbelong toorganization
            $table->string('user_organization_code', 64)->comment('userorganizationencoding')->default('');
            $table->string('friend_id', 64)->comment('goodfriendid');
            // goodfriendbelong toorganization
            $table->string('friend_organization_code', 64)->comment('goodfriendorganizationencoding')->default('');
            // goodfriendtype
            $table->tinyInteger('friend_type')->comment('goodfriendtype,0:ai 1:personcategory')->default(0);
            $table->string('remarks', 256)->comment('note');
            $table->string('extra', 1024)->comment('attachaddproperty');
            $table->tinyInteger('status')->comment('status,1:apply,2:agree 3:reject 4:ignore');
            $table->unique(['user_id', 'friend_id'], 'uk_user_id_friend_id');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('goodfriendtable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_chat_friends');
    }
}
