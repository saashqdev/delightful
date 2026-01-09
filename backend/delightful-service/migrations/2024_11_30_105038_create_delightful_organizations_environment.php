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
        Schema::create('delightful_organizations_environment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('login_code', 32)->comment('login码，useatassociateorganization和环境，caninlogin时手动填写。lengthmore短，便at记忆');
            $table->string('delightful_organization_code', 32)->comment('麦吉organization code');
            $table->string('origin_organization_code', 32)->comment('originalorganization code');
            // 环境id
            $table->unsignedBigInteger('environment_id')->comment('delightful_environment表的id。表明这个organization要use哪个环境');
            $table->unique('login_code', 'idx_login_code');
            $table->unique('delightful_organization_code', 'idx_delightful_organization_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_organizations_environment');
    }
};
