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
        if (Schema::hasTable('delightful_tenant')) {
            return;
        }
        Schema::create('delightful_tenant', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 255)->comment('企业name');
            $table->string('display_id', 255)->comment('企业编number，平台inside唯one');
            $table->tinyInteger('tenant_tag')->default(0)->comment('person版/team版标志. 1：team版 2：person版');
            $table->string('tenant_key', 32)->comment('企业标识');
            $table->text('avatar')->comment('企业avatar');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_key'], 'index_tenant_key');
            $table->index(['display_id'], 'index_display_id');
            $table->comment('企业name、企业编numberetc企业information');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_tenant');
    }
};
