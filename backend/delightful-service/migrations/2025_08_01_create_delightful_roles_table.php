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
        if (Schema::hasTable('delightful_roles')) {
            return;
        }
        Schema::create('delightful_roles', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->comment('角色名称');
            $table->json('permission_key')->nullable()->comment('角色permission列表');
            $table->string('organization_code', 64)->comment('organization编码');
            $table->tinyInteger('is_display')->default(1)->comment('是否展示: 0=否, 1=是');
            $table->json('permission_tag')->nullable()->comment('permission标签，用于前端展示分类');
            $table->tinyInteger('status')->default(1)->comment('status: 0=禁用, 1=启用');
            $table->string('created_uid', 64)->nullable()->comment('create者userID');
            $table->string('updated_uid', 64)->nullable()->comment('update者userID');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index(['organization_code'], 'idx_organization_code');

            $table->comment('RBAC角色表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_roles');
    }
};
