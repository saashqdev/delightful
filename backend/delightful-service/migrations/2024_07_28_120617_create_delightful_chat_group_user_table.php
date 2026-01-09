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
        // judge表whether存in
        if (Schema::hasTable('delightful_chat_group_users')) {
            return;
        }
        Schema::create('delightful_chat_group_users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('group_id', 64)->comment('群id');
            $table->string('user_id', 64)->comment('userid');
            $table->tinyInteger('user_role')->default(1)->comment('userrole,1:普通user；2：administrator 3:群主');
            $table->tinyInteger('user_type')->default(1)->comment('usertype,0:ai；1：personcategory. 冗remainderfield');
            $table->tinyInteger('status')->default(1)->comment('status,1:normal；2：禁言');
            $table->string('organization_code', 64)->comment('enter群o clock,user所inorganizationencoding');
            $table->unique(['group_id', 'user_id', 'organization_code'], 'uniq_idx_group_user');
            $table->softDeletes();
            $table->timestamps();
            $table->comment('群member表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_chat_group_users');
    }
};
