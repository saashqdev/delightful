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
        if (Schema::hasTable('delightful_organizations')) {
            return;
        }
        Schema::create('delightful_organizations', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('primary keyID');
            $table->string('delightful_organization_code', 100)->unique()->comment('organizationencoding');
            $table->string('name', 100)->comment('organizationname');
            $table->string('platform_type', 64)->nullable()->comment('平台type');
            $table->mediumText('logo')->nullable()->comment('organizationlogo');
            $table->mediumText('introduction')->nullable()->comment('企业description');
            $table->string('contact_user')->nullable()->comment('联系人');
            $table->string('contact_mobile', 32)->nullable()->comment('联系电话');
            $table->string('industry_type')->comment('organization行业type');
            $table->string('number', 32)->nullable()->comment('企业规模');
            $table->tinyInteger('status')->default(1)->comment('status 1:正常 2:disable');
            $table->string('creator_id', 64)->nullable()->comment('create人');
            $table->tinyInteger('type')->default(0)->comment('organizationtype 0:teamorganization 1:个人organization');
            $table->timestamps();
            $table->softDeletes();

            // 索引
            $table->index('delightful_organization_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_organizations');
    }
};
