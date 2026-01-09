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
        if (Schema::hasTable('delightful_contact_departments')) {
            return;
        }
        Schema::create('delightful_contact_departments', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('department_id', 64)->comment('麦吉departmentid');
            $table->string('parent_department_id', 64)->comment('父department的department ID')->nullable();
            $table->string('name', 64)->comment('departmentname');
            $table->text('i18n_name')->comment('国际化departmentname');
            $table->string('order', 64)->comment('department的sort，即departmentin其同leveldepartment的show顺序。取valuemore小sortmore靠front。')->nullable()->default('');
            $table->string('leader_user_id', 64)->comment('department主管的user ID')->nullable()->default('');
            $table->string('organization_code', 64)->comment('麦吉organizationencoding');
            $table->text('status')->comment('departmentstatus,jsonformat,目frontsupport is_deleted:whetherdelete');
            $table->string('document_id', 64)->comment('departmentinstruction书（云documentid）');
            // level
            $table->integer('level')->comment('departmentlayerlevel')->default(0);
            // path
            $table->text('path')->comment('departmentpath')->nullable();
            // department直属userperson数
            $table->integer('employee_sum')->comment('department直属userperson数')->default(0);
            $table->comment('userservice的department与the三方平台userrecordtable.useat与the三方平台实o clockdatasync,activaterecordetc');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_code', 'department_id'], 'org_department_id');
            $table->index(['organization_code', 'level'], 'org_department_level');
            $table->index(['organization_code', 'parent_department_id'], 'org_parent_department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_contact_departments');
    }
};
