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
        // rename表 delightful_token->delightful_tokens
        if (Schema::hasTable('delightful_token')) {
            Schema::rename('delightful_token', 'delightful_tokens');
        }
        Schema::table('delightful_tokens', static function (Blueprint $table) {
            $table->string('type_relation_value', 255)->comment(
                'tokentypeto应value.typefor0o clock,thisvalueforaccount_id;typefor1o clock,thisvalueforuser_id;typefor2o clock,thisvaluefororganizationencoding;typefor3o clock,thisvalueforapp_id;typefor4o clock,thisvalueforflow_id'
            )->default('')->change();
            // 判断 idx_token whether存in
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
