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
        Schema::create('delightful_user_id_relation', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 64)->comment('userid,organization下唯一.其他open_id,union_idneedaccording touser_idgenerate')->default('');
            $table->string('id_type', 12)->comment('idtype:open_id/union_id')->default('');
            $table->string('id_value', 64)->comment('idtype对应的值')->default('');
            $table->string('relation_type', 12)->comment('id对应的关联type:applicationencoding/create该application的organizationencoding')->default('');
            $table->string('relation_value', 64)->comment('id对应的关联type的值')->default('');
            // 确定唯一值,防conflict
            $table->unique(['id_type', 'id_value', 'relation_type', 'relation_value'], 'unq_id_relation');
            // 便于按organization/application等查找所有关联的user
            $table->index(['relation_type', 'relation_value'], 'idx_relation');
            $table->index(['user_id'], 'idx_user_id');
            $table->comment('userid关联表. record user_id 与 open_id/union_id等的关联');
            $table->datetimes();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_user_id_relation');
    }
};
