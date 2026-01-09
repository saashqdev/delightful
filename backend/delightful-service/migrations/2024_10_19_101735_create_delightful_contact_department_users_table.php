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
            $table->tinyInteger('is_leader')->comment('whetherisdepartmentleader 0-no 1-is')->default(0);
            $table->string('job_title', 64)->comment('inthisdepartmentposition')->default('');
            $table->string('leader_user_id', 64)->comment('inthisdepartmentdirect supervisor user_id')->nullable()->default('');
            $table->string('organization_code', 32)->comment('Magicorganizationencoding');
            $table->string('city', 64)->comment('workcity')->default('');
            $table->string('country', 32)->comment('countryorground区 Code abbreviation')->default('');
            $table->string('join_time', 32)->comment('onboardtime.secondleveltime戳format,table示from 1970 year 1 month 1 daystartpassed throughpasssecond数.')->default('');
            $table->string('employee_no', 32)->comment('工number')->default('');
            $table->tinyInteger('employee_type')->comment('employeetype.1:justtypeemployee2:intern3:outsidepackage4:labor 5:consultant');
            $table->string('orders', 256)->comment('usersortinfo.useatmarkaddress bookdownorganizationarchitectureperson员order,person员maybe存in多departmentmiddle,andhavedifferentsort')->nullable()->default('');
            $table->text('custom_attrs')->comment('customizefield.');
            $table->tinyInteger('is_frozen')->comment('whetherforpausestatususer.')->default(0);
            $table->comment('Magicdepartmentdownuserinfotable');
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
