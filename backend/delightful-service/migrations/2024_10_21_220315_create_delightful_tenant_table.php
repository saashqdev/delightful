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
            $table->string('name', 255)->comment('enterprisename');
            $table->string('display_id', 255)->comment('enterprise编number,platforminsideuniqueone');
            $table->tinyInteger('tenant_tag')->default(0)->comment('personversion/teamversionflag. 1:teamversion 2:personversion');
            $table->string('tenant_key', 32)->comment('enterpriseidentifier');
            $table->text('avatar')->comment('enterpriseavatar');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_key'], 'index_tenant_key');
            $table->index(['display_id'], 'index_display_id');
            $table->comment('enterprisename,enterprise编numberetcenterpriseinformation');
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
