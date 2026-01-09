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
        Schema::create('delightful_mcp_servers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code', 32)->comment('organizationencoding');
            $table->string('code', 80)->unique()->comment('唯一encoding');
            $table->string('name', 64)->default('')->comment('MCPservicename');
            $table->string('description', 255)->default('')->comment('MCPservicedescription');
            $table->string('icon', 255)->default('')->comment('MCPservicegraph标');
            $table->string('type', 16)->default('sse')->comment('servicetype: sseorstdio');
            $table->boolean('enabled')->default(false)->comment('whetherenable: 0-disable, 1-enable');
            $table->string('creator', 64)->default('')->comment('create者');
            $table->dateTime('created_at')->comment('creation time');
            $table->string('modifier', 64)->default('')->comment('修改者');
            $table->dateTime('updated_at')->comment('update time');
            $table->softDeletes();

            $table->unique(['organization_code', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_mcp_servers');
    }
};
