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
            // todo xhy 目frontis这么简短design，已and大白and陪哥discussion 2024-12-30
            $table->boolean('start_page')->default(false)->comment('start页switch');
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
