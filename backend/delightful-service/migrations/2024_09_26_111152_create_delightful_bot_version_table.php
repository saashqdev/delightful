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
        Schema::create('delightful_bot_versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('flow_code')->comment('workflowcode');
            $table->string('flow_version')->comment('workflowversion');
            $table->json('instruct')->comment('交互instruction');
            $table->bigInteger('root_id')->comment('根id');
            $table->string('robot_name')->comment('assistant name');
            $table->string('robot_avatar')->comment('assistant avatar');
            $table->string('robot_description')->comment('助理description');

            $table->string('version_description', 255)->default('')->comment('description');
            $table->string('version_number')->nullable()->comment('version number');
            $table->integer('release_scope')->nullable()->comment('publish范围.1:publish到企业内部 2:publish到应用市场');

            $table->integer('approval_status')->default(3)->nullable(false)->comment('approvalstatus');
            $table->integer('review_status')->default(0)->nullable(false)->comment('审核status');
            $table->integer('enterprise_release_status')->default(0)->nullable(false)->comment('publish到企业内部status');
            $table->integer('app_market_status')->default(0)->nullable(false)->comment('publish到应用市场status');

            $table->string('organization_code')->comment('organization编码');

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
        Schema::dropIfExists('delightful_bot_version');
    }
};
