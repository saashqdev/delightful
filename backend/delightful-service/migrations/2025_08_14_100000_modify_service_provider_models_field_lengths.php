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
        // modify service_provider table name fieldlength
        Schema::table('service_provider', function (Blueprint $table) {
            $table->string('name', 255)->comment('servicequotientname')->change();
        });

        // modify service_provider_models tablerelatedclosefieldlength
        Schema::table('service_provider_models', function (Blueprint $table) {
            $table->string('name', 255)->comment('modelname')->change();
            $table->string('model_version', 255)->comment('modelinservicequotientdownname')->change();
            $table->string('model_id', 255)->comment('modeltrueactualID')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // rollback service_provider table name fieldlength
        Schema::table('service_provider', function (Blueprint $table) {
            $table->string('name', 50)->comment('servicequotientname')->change();
        });

        // rollback service_provider_models tablerelatedclosefieldlength
        Schema::table('service_provider_models', function (Blueprint $table) {
            $table->string('name', 50)->comment('modelname')->change();
            $table->string('model_version', 50)->comment('modelinservicequotientdownname')->change();
            $table->string('model_id', 50)->comment('modeltrueactualID')->change();
        });
    }
};
