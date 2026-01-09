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
        Schema::create('delightful_operation_permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code', 20)->comment('organizationencoding');
            $table->unsignedTinyInteger('resource_type')->comment('resourcetype');
            $table->string('resource_id', 50)->comment('resourceid');
            $table->unsignedTinyInteger('target_type')->comment('goaltype');
            $table->string('target_id', 50)->comment('goalid');
            $table->unsignedTinyInteger('operation')->comment('operationas');
            $table->string('created_uid', 50)->comment('createperson');
            $table->string('updated_uid', 50)->comment('modifyperson');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_code', 'resource_type', 'resource_id'], 'idx_organization_resource');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_operation_permissions');
    }
};
