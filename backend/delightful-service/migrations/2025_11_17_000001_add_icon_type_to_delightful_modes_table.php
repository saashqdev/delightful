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
        Schema::table('delightful_modes', function (Blueprint $table) {
            $table->tinyInteger('icon_type')->default(1)->comment('icon类型 1:icon 2:图片')->after('icon');
            $table->string('icon_url', 512)->default('')->comment('icon图片URL')->after('icon_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_modes', function (Blueprint $table) {
        });
    }
};
