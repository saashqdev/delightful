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
        if (Schema::hasTable('delightful_contact_third_platform_users')) {
            return;
        }
        Schema::create('delightful_contact_third_platform_users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('delightful_id', 64)->nullable()->comment('delightful_user_account table delightful_id');
            $table->string('delightful_user_id', 64)->nullable()->comment('delightful_user_organization table user_id');
            $table->string('delightful_organization_code', 32)->comment('麦吉userbody系downorganizationcode');
            $table->string('third_user_id', 128)->comment('thethree方平台userid');
            $table->string('third_union_id', 128)->comment('thethree方平台user union_id');
            $table->string('third_platform_type', 32)->comment('thethree方平台type dingTalk/lark/weCom/teamShare');
            $table->string('third_employee_no', 64)->nullable()->default('')->comment('工number');
            $table->string('third_real_name', 64)->comment('员工姓名');
            $table->string('third_nick_name', 64)->nullable()->default('')->comment('员工昵称');
            $table->text('third_avatar')->nullable()->comment('avatar');
            $table->tinyInteger('third_gender')->default(0)->comment('员工property别 0-unknown 1-男 2-女');
            $table->string('third_email', 128)->nullable()->default('')->comment('邮箱');
            $table->string('third_mobile', 64)->nullable()->default('')->comment('thethree方平台员工hand机number');
            $table->string('third_id_number', 64)->nullable()->default('')->comment('员工身share证');
            $table->text('third_platform_users_extra')->comment('额outsideinfo');
            $table->index('delightful_user_id', 'delightful_user_id');
            $table->unique(['third_union_id', 'third_platform_type', 'delightful_organization_code'], 'unique_third_id');
            $table->softDeletes();
            $table->timestamps();
            $table->comment('thethree方平台syncpasscomeuserinfotable. notpassday书havepoint特殊,can直接day书userwhen做麦吉user.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_contact_third_platform_users');
    }
};
