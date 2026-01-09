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
        Schema::create('delightful_bots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('bot_version_id')->comment('助理绑定的versionid');
            $table->string('flow_code')->comment('workflowid');
            $table->json('instructs')->comment('交互instruction');
            $table->string('robot_name')->comment('assistant name');
            $table->string('robot_avatar')->comment('assistant avatar');
            $table->string('robot_description')->comment('助理description');
            $table->string('organization_code')->comment('organization编码');
            $table->integer('status')->comment('助理status:启用｜禁用');
            $table->string('created_uid')->default('')->comment('publish人');
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
        Schema::dropIfExists('delightful_bot_versions');
    }
};
