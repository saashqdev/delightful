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
        Schema::table('delightful_environments', function (Blueprint $table) {
            $table->json('private_config')->comment('Mage自己的一些configuration');
            // 重命名 config field为 open_platform_config
            $table->renameColumn('config', 'open_platform_config');
            $table->timestamp('deleted_at')->nullable()->comment('delete时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
