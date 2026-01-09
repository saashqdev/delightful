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
        Schema::table('delightful_contact_third_platform_id_mapping', static function (Blueprint $table) {
            $table->dropIndex('unique_origin_id_mapping_type');
            // 为了检查不同第third-party平台organization的user是否已经映射过，need调整索引 key的顺序
            $table->unique(['origin_id', 'mapping_type', 'delightful_organization_code', 'third_platform_type'], 'unique_origin_id_mapping_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
