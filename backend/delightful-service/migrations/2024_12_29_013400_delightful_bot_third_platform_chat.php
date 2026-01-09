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
        Schema::create('delightful_bot_third_platform_chat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('bot_id', 64)->default('')->comment('机器personID');
            $table->string('key', 64)->comment('唯one标识')->unique();
            $table->string('type', 64)->default('')->comment('平台type');
            $table->boolean('enabled')->default(true)->comment('whetherenable');
            $table->text('options')->comment('configuration');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
        });
    }
};
