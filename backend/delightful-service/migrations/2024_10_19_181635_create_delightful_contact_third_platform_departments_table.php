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
        if (Schema::hasTable('delightful_contact_third_platform_departments')) {
            return;
        }
        Schema::create('delightful_contact_third_platform_departments', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('delightful_department_id', 64)->comment('麦吉departmentid');
            $table->string('delightful_organization_code', 64)->comment('麦吉organizationencoding');
            $table->string('third_leader_user_id', 64)->comment('department主管的user ID')->nullable()->default('');
            $table->string('third_department_id', 64)->comment('第三方departmentid');
            $table->string('third_parent_department_id', 64)->comment('第三方父department的department ID')->nullable();
            $table->string('third_name', 64)->comment('第三方departmentname');
            $table->text('third_i18n_name')->comment('第三方国际化departmentname');
            $table->string('third_platform_type')->comment('第三方平台type dingTalk/lark/weCom/teamShare');
            $table->text('third_platform_departments_extra')->comment('额外info.第三方departmentstatus,jsonformat,目前support is_deleted:是否delete');
            $table->comment('userservice的department与第三方平台userrecordtable.用于与第三方平台实时datasync,activaterecord等');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['third_platform_type', 'third_department_id', 'delightful_organization_code'], 'org_platform_department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_contact_third_platform_departments');
    }
};
