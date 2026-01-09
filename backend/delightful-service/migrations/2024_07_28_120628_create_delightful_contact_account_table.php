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
        if (Schema::hasTable('delightful_contact_accounts')) {
            return;
        }
        Schema::create('delightful_contact_accounts', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('delightful_id', 64)->comment('账号id,跨租户(organization)唯一. 为了避免与user_id(organization内唯一)的概念混淆,therefore起名了delightful_id')->default('');
            // 账号type
            $table->tinyInteger('type')->comment('账号type,0:ai,1:人类')->default(0);
            // ai_code
            $table->string('ai_code', 64)->comment('aiencoding')->default('');
            // 账号status
            $table->tinyInteger('status')->comment('账号status,0:正常,1:disable')->default(0);
            // 国际冠码
            $table->string('country_code', 16)->comment('国际冠码')->default('');
            // 手机号
            $table->string('phone', 64)->comment('手机号')->default('');
            // 邮箱
            $table->string('email', 64)->comment('邮箱')->default('');
            // 真名
            $table->string('real_name', 64)->comment('真名')->default('');
            // 性别
            $table->tinyInteger('gender')->comment('性别，0:未知；1:男；2:女')->default(0);
            // 附加property
            $table->string('extra', 1024)->comment('附加property.')->default('');

            // 索引set
            $table->index(['status', 'type'], 'idx_status_type');
            $table->unique(['delightful_id'], 'unq_delightful_id');
            $table->unique(['country_code', 'phone'], 'unq_country_code_phone');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('user账号table,recorduser跨organization唯一的info,such as的手机号/真名/性别/usertypeetc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_contact_accounts');
    }
};
