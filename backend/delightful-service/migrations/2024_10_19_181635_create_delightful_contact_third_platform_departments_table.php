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
            $table->string('delightful_department_id', 64)->comment('Magicdepartmentid');
            $table->string('delightful_organization_code', 64)->comment('Magicorganizationencoding');
            $table->string('third_leader_user_id', 64)->comment('departmentsupervisoruser ID')->nullable()->default('');
            $table->string('third_department_id', 64)->comment('thethreesidedepartmentid');
            $table->string('third_parent_department_id', 64)->comment('thethreeparentsidedepartmentdepartment ID')->nullable();
            $table->string('third_name', 64)->comment('thethreesidedepartmentname');
            $table->text('third_i18n_name')->comment('thethreesideinternationalizationdepartmentname');
            $table->string('third_platform_type')->comment('thethreesideplatformtype dingTalk/lark/weCom/teamShare');
            $table->text('third_platform_departments_extra')->comment('quotaoutsideinfo.thethreesidedepartmentstatus,jsonformat,itemfrontsupport is_deleted:whetherdelete');
            $table->comment('userservicedepartmentandthethreesideplatformuserrecordtable.useatandthethreesideplatformactualo clockdatasync,activaterecordetc');
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
