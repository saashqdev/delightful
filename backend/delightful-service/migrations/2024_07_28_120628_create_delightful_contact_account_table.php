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
            $table->string('delightful_id', 64)->comment('账numberid,跨租户(organization)唯one. for避免anduser_id(organizationinside唯one)概念混淆,thereforeup名delightful_id')->default('');
            // 账numbertype
            $table->tinyInteger('type')->comment('账numbertype,0:ai,1:personcategory')->default(0);
            // ai_code
            $table->string('ai_code', 64)->comment('aiencoding')->default('');
            // 账numberstatus
            $table->tinyInteger('status')->comment('账numberstatus,0:normal,1:disable')->default(0);
            // 国际冠码
            $table->string('country_code', 16)->comment('国际冠码')->default('');
            // hand机number
            $table->string('phone', 64)->comment('hand机number')->default('');
            // 邮箱
            $table->string('email', 64)->comment('邮箱')->default('');
            // true名
            $table->string('real_name', 64)->comment('true名')->default('');
            // property别
            $table->tinyInteger('gender')->comment('property别，0:unknown；1:男；2:女')->default(0);
            // attachaddproperty
            $table->string('extra', 1024)->comment('attachaddproperty.')->default('');

            // indexset
            $table->index(['status', 'type'], 'idx_status_type');
            $table->unique(['delightful_id'], 'unq_delightful_id');
            $table->unique(['country_code', 'phone'], 'unq_country_code_phone');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('user账numbertable,recorduser跨organization唯oneinfo,such ashand机number/true名/property别/usertypeetc');
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
