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
        Schema::create('delightful_mcp_server_tools', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code', 32)->comment('organizationencoding');
            $table->string('mcp_server_code', 80)->comment('associate的 mcp 服务 code')->index();
            $table->string('name', 64)->default('')->comment('toolname');
            $table->string('description', 512)->default('')->comment('tooldescription');
            $table->tinyInteger('source')->default(0)->comment('tool来源');
            $table->string('rel_code', 80)->default('')->comment('associate的tool code');
            $table->string('rel_version_code', 80)->default('')->comment('associate的toolversion code');
            $table->json('rel_info')->nullable()->comment('associate的information');
            $table->string('version', 80)->default('')->comment('toolversion');
            $table->boolean('enabled')->default(false)->comment('是否enable: 0-disable, 1-enable');
            $table->json('options')->nullable()->comment('toolconfiguration name、description、inputSchema');
            $table->string('creator', 64)->default('')->comment('create者');
            $table->dateTime('created_at')->comment('creation time');
            $table->string('modifier', 64)->default('')->comment('修改者');
            $table->dateTime('updated_at')->comment('update time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_mcp_server_tools');
    }
};
