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
            $table->string('department_id', 64)->comment('Magicdepartmentid');
            $table->string('parent_department_id', 64)->comment('parentdepartmentdepartment ID')->nullable();
            $table->string('name', 64)->comment('departmentname');
            $table->text('i18n_name')->comment('internationalizationdepartmentname');
            $table->string('order', 64)->comment('departmentsort,即departmentinitssameleveldepartmentshoworder.getvaluemoresmallsortmore靠front.')->nullable()->default('');
            $table->string('leader_user_id', 64)->comment('departmentsupervisoruser ID')->nullable()->default('');
            $table->string('organization_code', 64)->comment('Magicorganizationencoding');
            $table->text('status')->comment('departmentstatus,jsonformat,itemfrontsupport is_deleted:whetherdelete');
            $table->string('document_id', 64)->comment('departmentinstructionbook(clouddocumentid)');
            // level
            $table->integer('level')->comment('departmentlayerlevel')->default(0);
            // path
            $table->text('path')->comment('departmentpath')->nullable();
            // departmentdirectly underuserpersoncount
            $table->integer('employee_sum')->comment('departmentdirectly underuserpersoncount')->default(0);
            $table->comment('userservicedepartmentandthethreesideplatformuserrecordtable.useatandthethreesideplatformactualo clockdatasync,activaterecordetc');
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
