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
        Schema::create('delightful_flow_multi_modal_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('message_id', 64)->default('')->comment('messageID')->index();
            $table->tinyInteger('type')->default(0)->comment('多模statetype。1 image');
            $table->string('model', 128)->default('')->comment('识别所usemodel');
            $table->text('analysis_result')->comment('analyzeresult');
            $table->datetimes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_flow_multi_modal_logs');
    }
};
