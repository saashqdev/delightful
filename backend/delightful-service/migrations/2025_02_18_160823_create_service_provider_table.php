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
        if (Schema::hasTable('service_provider')) {
            return;
        }

        Schema::create('service_provider', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 50)->comment('servicequotientname');
            $table->string('provider_code', 50)->comment('servicequotientencoding,indicatebelongatwhich AI servicequotient.like:official,DS,prefixwithincloudetc');
            $table->string('description', 255)->nullable()->comment('servicequotientdescription');
            $table->string('icon', 255)->nullable()->comment('servicequotientgraphmark');
            $table->tinyInteger('provider_type')->default(0)->comment('servicequotienttype:0-normal,1-official');
            $table->string('category', 20)->comment('category:llm-bigmodel,vlm-visualmodel');
            $table->tinyInteger('status')->default(0)->comment('status:0-notenable,1-enable');
            $table->tinyInteger('is_models_enable')->default(0)->comment('modelcolumntableget:0-notenable,1-enable');
            $table->timestamps();
            $table->softDeletes();
            $table->index('category', 'idx_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_provider');
    }
};
