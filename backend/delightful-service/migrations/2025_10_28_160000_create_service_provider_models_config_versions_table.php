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
        if (Schema::hasTable('service_provider_models_config_versions')) {
            return;
        }

        Schema::create('service_provider_models_config_versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('service_provider_model_id')->comment('modelID,associateservice_provider_models.id');

            $table->decimal('creativity', 3, 2)->default(0.5)->comment('createcapabilityparameter');
            $table->integer('max_tokens')->nullable()->comment('mostbigtokencount');
            $table->decimal('temperature', 3, 2)->nullable()->comment('温degreeparameter');
            $table->integer('vector_size')->default(2048)->comment('toquantity维degree');
            $table->string('billing_type', 50)->nullable()->comment('billingtype');
            $table->decimal('time_pricing', 10, 4)->nullable()->comment('timepricing');
            $table->decimal('input_pricing', 10, 4)->nullable()->comment('inputpricing');
            $table->decimal('output_pricing', 10, 4)->nullable()->comment('outputpricing');
            $table->string('billing_currency', 10)->nullable()->comment('billingcurrency');
            $table->boolean('support_function')->default(false)->comment('whethersupportfunctioncall');
            $table->decimal('cache_hit_pricing', 10, 4)->nullable()->comment('cache命middlepricing');
            $table->integer('max_output_tokens')->nullable()->comment('mostbigoutputtokencount');
            $table->boolean('support_embedding')->default(false)->comment('whethersupportembedding');
            $table->boolean('support_deep_think')->default(false)->comment('whethersupportdeepdegreethink');
            $table->decimal('cache_write_pricing', 10, 4)->nullable()->comment('cachewritepricing');
            $table->boolean('support_multi_modal')->default(false)->comment('whethersupportmulti-modalstate');
            $table->boolean('official_recommended')->default(false)->comment('whetherofficialrecommended');
            $table->decimal('input_cost', 10, 4)->nullable()->comment('inputcost');
            $table->decimal('output_cost', 10, 4)->nullable()->comment('outputcost');
            $table->decimal('cache_hit_cost', 10, 4)->nullable()->comment('cache命middlecost');
            $table->decimal('cache_write_cost', 10, 4)->nullable()->comment('cachewritecost');
            $table->integer('version')->default(1)->comment('versionnumber');
            $table->boolean('is_current_version')->default(true)->comment('whethercurrentversion:1-is,0-no');
            $table->timestamps();

            $table->index(['service_provider_model_id', 'is_current_version'], 'idx_model_id_is_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
