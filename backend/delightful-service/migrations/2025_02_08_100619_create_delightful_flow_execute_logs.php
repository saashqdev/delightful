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
        Schema::dropIfExists('delightful_flow_execute_logs');
        Schema::create('delightful_flow_execute_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('execute_data_id')->default('')->comment('executedataID');
            $table->string('conversation_id')->default('')->comment('conversationID');
            $table->string('flow_code')->default('')->comment('processencoding');
            $table->string('flow_version_code')->default('')->comment('versionencoding');
            $table->integer('status')->default(0)->comment('status 1 准备运line;2 运linemiddle;3 complete;4 failed;5 cancel')->index();
            $table->json('ext_params')->nullable()->comment('extensionparameter');
            $table->json('result')->nullable()->comment('result');
            $table->timestamp('created_at')->nullable()->comment('creation time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_flow_execute_logs');
    }
};
