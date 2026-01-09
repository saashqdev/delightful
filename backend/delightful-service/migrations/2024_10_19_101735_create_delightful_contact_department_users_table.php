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
        if (Schema::hasTable('delightful_contact_department_users')) {
            return;
        }
        Schema::create('delightful_contact_department_users', static function (Blueprint $table) {
            $table->bigIncrements('id');
            // delightful_id
            $table->string('delightful_id', 64)->comment('delightful_contact_account table的 delightful_id')->default('');
            // delightful_user_id
            $table->string('user_id', 64)->comment('delightful_contact_user table的 user_id')->default('');
            $table->string('department_id', 64)->comment('departmentid');
            $table->tinyInteger('is_leader')->comment('whether是department领导 0-否 1-是')->default(0);
            $table->string('job_title', 64)->comment('in此department的职位')->default('');
            $table->string('leader_user_id', 64)->comment('in此department的直属领导的 user_id')->nullable()->default('');
            $table->string('organization_code', 32)->comment('麦吉的organizationencoding');
            $table->string('city', 64)->comment('工作城市')->default('');
            $table->string('country', 32)->comment('国家or地区 Code 缩写')->default('');
            $table->string('join_time', 32)->comment('入职time。秒级time戳format，table示from 1970 年 1 月 1 日start所经过的秒数。')->default('');
            $table->string('employee_no', 32)->comment('工号')->default('');
            $table->tinyInteger('employee_type')->comment('员工type。1：正式员工2：实习生3：外package4：劳务 5：顾问');
            $table->string('orders', 256)->comment('usersortinfo。useatmark通讯录下organization架构的人员顺序，人员可能存in多个department中，andhavedifferent的sort')->nullable()->default('');
            $table->text('custom_attrs')->comment('customizefield。');
            $table->tinyInteger('is_frozen')->comment('whether为pausestatus的user。')->default(0);
            $table->comment('麦吉department下的userinfotable');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_code', 'delightful_id'], 'org_delightful_id');
            $table->index(['department_id'], 'index_department_id');
            $table->index(['user_id'], 'index_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_contact_department_users');
    }
};
