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
        // 重命名表 delightful_token->delightful_tokens
        if (Schema::hasTable('delightful_token')) {
            Schema::rename('delightful_token', 'delightful_tokens');
        }
        Schema::table('delightful_tokens', static function (Blueprint $table) {
            $table->string('type_relation_value', 255)->comment(
                'tokentype对应的值.type为0时,此值为account_id;type为1时,此值为user_id;type为2时,此值为organization编码;type为3时,此值为app_id;type为4时,此值为flow_id'
            )->default('')->change();
            // 判断 idx_token 是否存在
            if (Schema::hasIndex('delightful_tokens', 'idx_token')) {
                $table->dropIndex('idx_token');
            }
            if (! Schema::hasIndex('delightful_tokens', 'unq_token_type')) {
                $table->unique(['token', 'type'], 'unq_token_type');
            }
            if (! Schema::hasIndex('delightful_tokens', 'idx_type_relation_value_expired_at')) {
                $table->index(['type', 'type_relation_value', 'expired_at'], 'idx_type_relation_value_expired_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_tokens');
    }
};
