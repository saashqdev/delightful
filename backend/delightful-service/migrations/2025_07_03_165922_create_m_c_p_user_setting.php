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
        Schema::create('delightful_mcp_user_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code', 32)->default('')->comment('organizationencoding');
            $table->string('user_id', 64)->comment('userID');
            $table->string('mcp_server_id', 80)->comment('MCP服务ID')->index();
            $table->json('require_fields')->nullable()->comment('必填字段');
            $table->json('oauth2_auth_result')->nullable()->comment('OAuth2authenticationresult');
            $table->json('additional_config')->nullable()->comment('附加configuration');
            $table->string('creator', 64)->default('')->comment('create者');
            $table->dateTime('created_at')->comment('creation time');
            $table->string('modifier', 64)->default('')->comment('修改者');
            $table->dateTime('updated_at')->comment('update time');

            $table->index(['organization_code', 'user_id', 'mcp_server_id'], 'idx_org_user_mcp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
