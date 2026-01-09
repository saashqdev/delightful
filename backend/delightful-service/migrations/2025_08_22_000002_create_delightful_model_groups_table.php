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
        if (Schema::hasTable('delightful_mode_groups')) {
            return;
        }

        Schema::create('delightful_mode_groups', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('mode_id')->unsigned()->default(0)->comment('modeID');
            $table->json('name_i18n')->comment('minutegroupname国际化');
            $table->string('icon', 255)->default('')->comment('minutegroup图标');
            $table->string('color', 10)->default('')->comment('minutegroupcolor');
            $table->text('description')->comment('minutegroupdescription');
            $table->integer('sort')->default(0)->comment('sort权重');
            $table->tinyInteger('status')->default(1)->comment('status 0:disable 1:enable');
            $table->string('organization_code', 32)->default('')->comment('organizationcode');
            $table->string('creator_id', 64)->default('')->comment('create人ID');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
