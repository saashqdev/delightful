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
        Schema::create('delightful_flow_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_hash', 32)->unique()->comment('cache键MD5hashvalue(cache_prefix+cache_key)');
            $table->string('cache_prefix')->comment('cachefront缀');
            $table->string('cache_key')->comment('cache键名');
            $table->string('scope_tag', 10)->comment('asuse域标识');
            $table->longText('cache_value')->comment('cachevaluecontent');
            $table->unsignedInteger('ttl_seconds')->default(7200)->comment('TTLsecond数（0representpermanentcache）');
            $table->timestamp('expires_at')->comment('expiretime戳');
            $table->string('organization_code', 64)->comment('organization隔离');
            $table->string('created_uid', 64)->default('')->comment('createperson');
            $table->string('updated_uid', 64)->default('')->comment('updateperson');
            $table->timestamps();

            // 索引 - useMD5hashvalueasformainquery索引
            $table->unique('cache_hash', 'uk_cache_hash');
            $table->index('expires_at', 'idx_expires_at');
            $table->index('organization_code', 'idx_organization_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_flow_cache');
    }
};
