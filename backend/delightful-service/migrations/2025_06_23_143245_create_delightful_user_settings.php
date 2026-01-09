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
        Schema::create('delightful_user_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code', 32)->default('')->comment('organizationencoding');
            $table->string('user_id', 64)->comment('userID');
            $table->string('key', 80)->comment('settingkey');
            $table->json('value')->comment('settingvalue');
            $table->string('creator', 100)->comment('create者');
            $table->timestamp('created_at')->nullable()->comment('createtime');
            $table->string('modifier', 100)->comment('modify者');
            $table->timestamp('updated_at')->nullable()->comment('updatetime');

            $table->index(['organization_code', 'user_id'], 'idx_org_user');
            $table->unique(['organization_code', 'user_id', 'key'], 'uk_org_user_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_user_settings');
    }
};
