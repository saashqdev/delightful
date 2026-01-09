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
            $table->bigInteger('bot_version_id')->comment('assistantbindversionid');
            $table->string('flow_code')->comment('workflowid');
            $table->json('instructs')->comment('interactioninstruction');
            $table->string('robot_name')->comment('assistant name');
            $table->string('robot_avatar')->comment('assistant avatar');
            $table->string('robot_description')->comment('assistantdescription');
            $table->string('organization_code')->comment('organizationencoding');
            $table->integer('status')->comment('assistantstatus:enable｜disable');
            $table->string('created_uid')->default('')->comment('publishperson');
            $table->timestamp('created_at')->nullable()->comment('creation time');
            $table->string('updated_uid')->default('')->comment('updatepersonuserID');
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
