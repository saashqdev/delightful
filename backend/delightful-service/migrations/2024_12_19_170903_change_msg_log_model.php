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
        Schema::table('delightful_api_msg_logs', function (Blueprint $table) {
            $table->text('msg')->nullable()->change();
            $table->string('app_code', 64)->default('')->comment('applicationencoding');
            $table->string('business_id', 64)->default('')->comment('business id');
            $table->integer('use_token')->default(0);
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
