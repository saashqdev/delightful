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
        Schema::create('delightful_api_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code', 32)->default('')->comment('organizationencoding');
            $table->string('code', 64)->default('')->comment('encoding');
            $table->string('name', 64)->default('')->comment('name');
            $table->string('description', 255)->default('')->comment('description');
            $table->string('icon', 255)->default('')->comment('icon');
            $table->string('created_uid', 80)->default('')->comment('createperson');
            $table->dateTime('created_at')->comment('createtime');
            $table->string('updated_uid', 80)->default('')->comment('modifyperson');
            $table->dateTime('updated_at')->comment('modifytime');
            $table->softDeletes();

            $table->unique(['organization_code', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_applications');
    }
};
