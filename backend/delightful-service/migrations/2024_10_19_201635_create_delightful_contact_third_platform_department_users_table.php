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
            $table->string('delightful_organization_code', 32)->comment('Magicorganizationencoding');
            $table->string('third_department_id', 64)->comment('thethree方departmentid');
            $table->string('third_union_id')->comment('thethree方platformuserunion_id');
            $table->string('third_platform_type', 32)->comment('thethree方platformtype dingTalk/lark/weCom/teamShare');
            $table->tinyInteger('third_is_leader')->comment('whetherisdepartmentleader 0-no 1-is')->default(0);
            $table->string('third_job_title', 64)->comment('inthisdepartmentposition')->default('');
            $table->string('third_leader_user_id', 64)->comment('inthisdepartmentdirect supervisor user_id')->default('');
            $table->string('third_city', 64)->comment('workcity')->default('');
            $table->string('third_country', 32)->comment('countryorground区 Code abbreviation')->default('CN');
            $table->string('third_join_time', 64)->comment('onboardtime.secondleveltime戳format,table示from 1970 year 1 month 1 daystartpassed throughpasssecond数.');
            $table->string('third_employee_no', 32)->comment('工number')->default('');
            $table->tinyInteger('third_employee_type')->comment('employeetype.1:justtypeemployee2:intern3:outsidepackage4:labor 5:consultant')->default(1);
            $table->text('third_custom_attrs')->comment('customizefield.');
            $table->text('third_department_path')->comment('departmentpath.');
            $table->text('third_platform_department_users_extra')->comment('额outsideinfo');
            $table->comment('userservicedepartmentandthethree方platformuserrecordtable.useatandthethree方platform实o clockdatasync,activaterecordetc');
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
