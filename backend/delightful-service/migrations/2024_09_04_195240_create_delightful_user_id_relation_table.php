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
            $table->string('user_id', 64)->comment('userid,organizationdown唯一.其他open_id,union_idneedaccording touser_idgenerate')->default('');
            $table->string('id_type', 12)->comment('idtype:open_id/union_id')->default('');
            $table->string('id_value', 64)->comment('idtype对应的value')->default('');
            $table->string('relation_type', 12)->comment('id对应的associatetype:applicationencoding/create该application的organizationencoding')->default('');
            $table->string('relation_value', 64)->comment('id对应的associatetype的value')->default('');
            // 确定唯一value,防conflict
            $table->unique(['id_type', 'id_value', 'relation_type', 'relation_value'], 'unq_id_relation');
            // 便at按organization/applicationetcfind所haveassociate的user
            $table->index(['relation_type', 'relation_value'], 'idx_relation');
            $table->index(['user_id'], 'idx_user_id');
            $table->comment('useridassociate表. record user_id 与 open_id/union_idetc的associate');
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
