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
        if (Schema::hasTable('delightful_tokens')) {
            return;
        }
        Schema::create('delightful_tokens', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('type')->default(0)->comment('tokentype. 0:账号,1:user,2:organization,3:application,4:process');
            $table->string('type_relation_value', 64)->comment(
                'tokentype对应的value.type为0时,此value为account_id;type为1时,此value为user_id;type为2时,此value为organizationencoding;type为3时,此value为app_id;type为4时,此value为flow_id'
            );
            $table->string('token', 256)->comment('token的value,全局唯一');
            $table->timestamp('expired_at')->comment('expire时间');
            $table->unique(['token'], 'idx_token');
            $table->timestamps();
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
