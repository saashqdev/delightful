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
        if (! Schema::hasTable('delightful_ai_abilities')) {
            return;
        }

        Schema::table('delightful_ai_abilities', function (Blueprint $table) {
            // 将 config 字段从 json 改为 text type
            $table->text('config')->change()->comment('configurationinformation（AES加密后的JSON字符串）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('delightful_ai_abilities')) {
            return;
        }

        Schema::table('delightful_ai_abilities', function (Blueprint $table) {
            // 回滚：将 config 字段改回 json type
            $table->json('config')->change()->comment('configurationinformation（provider_code, access_point, api_key, model_id等）');
        });
    }
};
