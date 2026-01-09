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
        Schema::table('delightful_bot_versions', function (Blueprint $table) {
            // todo xhy 目前是这么简短design，已和大白和陪哥讨论 2024-12-30
            $table->boolean('start_page')->default(false)->comment('启动页开关');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_bot_versions', function (Blueprint $table) {
        });
    }
};
