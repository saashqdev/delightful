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
            $table->unsignedBigInteger('type')->default(0)->comment('tokentype. 0:账number,1:user,2:organization,3:application,4:process');
            $table->string('type_relation_value', 64)->comment(
                'tokentypeto应value.typefor0o clock,thisvalueforaccount_id;typefor1o clock,thisvalueforuser_id;typefor2o clock,thisvaluefororganizationencoding;typefor3o clock,thisvalueforapp_id;typefor4o clock,thisvalueforflow_id'
            );
            $table->string('token', 256)->comment('tokenvalue,alllocally uniqueone');
            $table->timestamp('expired_at')->comment('expiretime');
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
