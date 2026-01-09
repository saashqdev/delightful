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
        Schema::create('delightful_flow_tool_sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code', 32)->comment('organization编码');
            $table->string('code', 80)->comment('tool集编码');
            $table->string('name', 64)->comment('tool集名称');
            $table->string('description', 255)->comment('tool集description');
            $table->string('icon', 255)->comment('tool集图标');
            $table->boolean('enabled')->default(true)->comment('是否启用');
            $table->string('created_uid', 80)->comment('create人');
            $table->dateTime('created_at')->comment('creation time');
            $table->string('updated_uid', 80)->comment('修改人');
            $table->dateTime('updated_at')->comment('modification time');
            $table->softDeletes();

            $table->unique(['organization_code', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
