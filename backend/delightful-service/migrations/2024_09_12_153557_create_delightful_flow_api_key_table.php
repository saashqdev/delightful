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
        Schema::create('delightful_flow_api_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code')->default('')->comment('organization编码');
            $table->string('code', 50)->default('')->comment('API Key编码')->index();
            $table->string('flow_code', 50)->default('')->comment('process编码')->index();
            $table->string('conversation_id', 50)->default('')->comment('conversationID');
            $table->integer('type')->default(0)->comment('type');
            $table->string('name')->default('')->comment('名称');
            $table->string('description')->default('')->comment('description');
            $table->string('secret_key', 50)->default('')->comment('密钥')->unique();
            $table->boolean('enabled')->default(false)->comment('是否启用');
            $table->timestamp('last_used')->nullable()->comment('最后use时间');
            $table->string('created_uid')->default('')->comment('create者userID');
            $table->timestamp('created_at')->nullable()->comment('creation time');
            $table->string('updated_uid')->default('')->comment('update者userID');
            $table->timestamp('updated_at')->nullable()->comment('update time');
            $table->timestamp('deleted_at')->nullable()->comment('deletion time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_flow_api_keys');
    }
};
