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
        Schema::create('delightful_environments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('deployment', '32')->comment('deploytype.official:saas|southeastAsia,私have:private');
            $table->string('environment', '32')->comment('environmenttype:test/production');
            $table->json('config')->comment('environmentconfigurationdetail');
            $table->timestamps();
            $table->unique(['deployment', 'environment'], 'unq_deployment_environment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_environments');
    }
};
