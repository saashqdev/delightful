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
        if (Schema::hasTable('delightful_contact_third_platform_department_users')) {
            return;
        }
        Schema::create('delightful_contact_third_platform_department_users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('delightful_department_id', 64)->comment('departmentid');
            $table->string('delightful_organization_code', 32)->comment('麦吉的organizationencoding');
            $table->string('third_department_id', 64)->comment('第三方departmentid');
            $table->string('third_union_id')->comment('第三方平台user的union_id');
            $table->string('third_platform_type', 32)->comment('第三方平台type dingTalk/lark/weCom/teamShare');
            $table->tinyInteger('third_is_leader')->comment('是否是department领导 0-否 1-是')->default(0);
            $table->string('third_job_title', 64)->comment('在此department的职位')->default('');
            $table->string('third_leader_user_id', 64)->comment('在此department的直属领导的 user_id')->default('');
            $table->string('third_city', 64)->comment('工作城市')->default('');
            $table->string('third_country', 32)->comment('国家或地区 Code 缩写')->default('CN');
            $table->string('third_join_time', 64)->comment('入职time。秒级time戳format，table示从 1970 年 1 月 1 日开始所经过的秒数。');
            $table->string('third_employee_no', 32)->comment('工号')->default('');
            $table->tinyInteger('third_employee_type')->comment('员工type。1：正式员工2：实习生3：外package4：劳务 5：顾问')->default(1);
            $table->text('third_custom_attrs')->comment('customizefield。');
            $table->text('third_department_path')->comment('departmentpath。');
            $table->text('third_platform_department_users_extra')->comment('额外info');
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
        Schema::dropIfExists('delightful_contact_third_platform_department_users');
    }
};
