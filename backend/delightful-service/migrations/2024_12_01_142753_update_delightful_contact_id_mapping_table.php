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
        // delete delightful_contact_id_mapping 表名
        Schema::dropIfExists('delightful_contact_id_mapping');
        // delete delightful_contact_third_platform_department_users/delightful_contact_third_platform_departments/delightful_contact_third_platform_users 表
        Schema::dropIfExists('delightful_contact_third_platform_department_users');
        Schema::dropIfExists('delightful_contact_third_platform_departments');
        Schema::dropIfExists('delightful_contact_third_platform_users');

        if (Schema::hasTable('delightful_contact_third_platform_id_mapping')) {
            return;
        }
        Schema::create('delightful_contact_third_platform_id_mapping', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('origin_id', 128)->comment('源id');
            $table->string('new_id', 64)->comment('newid');
            // mappingtype:user id,department id,nullbetween id,organizationencoding
            $table->string('mapping_type', 32)->comment('mappingtype(user,department,space,organization)');
            // thethird-party平台type:企业微信,钉钉,飞书
            $table->string('third_platform_type', 32)->comment('thethird-party平台type(wechat_work,dingtalk,lark)');
            // delightful body系organizationencoding
            $table->string('delightful_organization_code', 32)->comment('delightful body系organizationencoding');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['new_id', 'mapping_type'], 'new_id_mapping_type');
            $table->unique(['delightful_organization_code', 'third_platform_type', 'mapping_type', 'origin_id'], 'unique_origin_id_mapping_type');
            $table->comment('department,user,nullbetweenencodingetcmappingclose系record');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
