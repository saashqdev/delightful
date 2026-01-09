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
            $table->string('delightful_id', 64)->comment('delightful_contact_account table delightful_id')->default('');
            // delightful_user_id
            $table->string('user_id', 64)->comment('delightful_contact_user table user_id')->default('');
            $table->string('department_id', 64)->comment('departmentid');
            $table->tinyInteger('is_leader')->comment('whetherisdepartment领导 0-否 1-is')->default(0);
            $table->string('job_title', 64)->comment('inthisdepartment职位')->default('');
            $table->string('leader_user_id', 64)->comment('inthisdepartment直属领导 user_id')->nullable()->default('');
            $table->string('organization_code', 32)->comment('麦吉organizationencoding');
            $table->string('city', 64)->comment('work城市')->default('');
            $table->string('country', 32)->comment('国家orground区 Code 缩写')->default('');
            $table->string('join_time', 32)->comment('入职time。secondleveltime戳format，table示from 1970 year 1 month 1 daystart所经passsecond数。')->default('');
            $table->string('employee_no', 32)->comment('工number')->default('');
            $table->tinyInteger('employee_type')->comment('员工type。1：正type员工2：实习生3：outsidepackage4：劳务 5：顾问');
            $table->string('orders', 256)->comment('usersortinfo。useatmark通讯录downorganization架构person员顺序，person员maybe存in多departmentmiddle，andhavedifferentsort')->nullable()->default('');
            $table->text('custom_attrs')->comment('customizefield。');
            $table->tinyInteger('is_frozen')->comment('whetherforpausestatususer。')->default(0);
            $table->comment('麦吉departmentdownuserinfotable');
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
