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
        if (Schema::hasTable('delightful_contact_users')) {
            return;
        }
        Schema::create('delightful_contact_users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('delightful_id', 64)->comment('账号id,冗余field')->default('');
            // organizationencoding
            $table->string('organization_code', 64)->comment('organizationencoding')->default('');
            // user_id
            $table->string('user_id', 64)->comment('userid,organization下唯一.此field还willrecord一份到user_id_relation')->default(0);
            // user_type
            $table->tinyInteger('user_type')->comment('usertype,0:ai,1:人类')->default(0);
            $table->string('description', 1024)->comment('description(可用于ai的自我介绍)');
            $table->integer('like_num')->comment('like数')->default(0);
            $table->string('label', 256)->comment('自我tag，多个用逗号分隔')->default('');
            $table->tinyInteger('status')->comment('user在该organization的status,0:freeze,1:activated,2:已离职,3:已exit')->default(0);
            $table->string('nickname', 64)->comment('昵称')->default('');
            $table->text('i18n_name')->comment('国际化username');
            $table->string('avatar_url', 128)->comment('useravatarlink')->default('');
            $table->string('extra', 1024)->comment('附加property')->default('');
            $table->string('user_manual', 64)->comment('userinstruction书(云document)')->default('');
            // 索引set
            $table->unique(['user_id'], 'unq_user_organization_id');
            $table->unique(['delightful_id', 'organization_code'], 'unq_delightful_id_organization_code');
            $table->index(['organization_code'], 'idx_organization_code');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('organization的usertable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_contact_users');
    }
};
