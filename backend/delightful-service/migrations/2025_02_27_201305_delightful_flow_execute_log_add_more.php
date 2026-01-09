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
        Schema::table('delightful_flow_execute_logs', function (Blueprint $table) {
            $table->string('organization_code')->default('')->comment('organizationcode');
            $table->string('flow_type')->default('')->comment('processtype');
            $table->string('parent_flow_code')->default('')->comment('父processcode');
            $table->string('operator_id')->default('')->comment('操作员ID');
            $table->integer('level')->default(0)->comment('level别');
            $table->string('execution_type')->default('')->comment('executetype');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
        });
    }
};
