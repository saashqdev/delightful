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
        // add service_provider_models table索引
        Schema::table('service_provider_models', function (Blueprint $table) {
            // deleteold model_parent_id_status 索引（if存in）
            if (Schema::hasIndex('service_provider_models', 'service_provider_models_model_parent_id_status_index')) {
                $table->dropIndex('service_provider_models_model_parent_id_status_index');
            }

            // deleteold idx_organization_code_status_model_version 索引（if存in）
            if (Schema::hasIndex('service_provider_models', 'idx_organization_code_status_model_version')) {
                $table->dropIndex('idx_organization_code_status_model_version');
            }

            // deleteold idx_model_id_status_organization_code 索引（if存in）
            if (Schema::hasIndex('service_provider_models', 'idx_model_id_status_organization_code')) {
                $table->dropIndex('idx_model_id_status_organization_code');
            }

            // addorganization+status+category别索引（ifnot存in）
            if (! Schema::hasIndex('service_provider_models', 'idx_organization_status_category')) {
                $table->index(['organization_code', 'status', 'category'], 'idx_organization_status_category');
            }

            // addorganization+configurationID索引（ifnot存in）
            if (! Schema::hasIndex('service_provider_models', 'idx_organization_code_config_id')) {
                $table->index(['organization_code', 'service_provider_config_id'], 'idx_organization_code_config_id');
            }

            // addnewgroup合索引：organization_code, model_parent_id（ifnot存in）
            if (! Schema::hasIndex('service_provider_models', 'idx_org_model_parent')) {
                $table->index(['organization_code', 'model_parent_id'], 'idx_org_model_parent');
            }
        });

        // add service_provider_configs table索引
        Schema::table('service_provider_configs', function (Blueprint $table) {
            // organization+status联合索引（ifnot存in）
            if (! Schema::hasIndex('service_provider_configs', 'idx_org_status')) {
                $table->index(['organization_code', 'status'], 'idx_org_status');
            }

            // organization+service商ID联合索引（ifnot存in）
            if (! Schema::hasIndex('service_provider_configs', 'idx_org_provider_id')) {
                $table->index(['organization_code', 'service_provider_id'], 'idx_org_provider_id');
            }
        });

        // add service_provider_original_models table索引
        Schema::table('service_provider_original_models', function (Blueprint $table) {
            // 核coregroup合索引（ifnot存in）
            if (! Schema::hasIndex('service_provider_original_models', 'idx_org_type')) {
                $table->index(['organization_code', 'type'], 'idx_org_type');
            }

            // type+organization+modelID联合索引（ifnot存in）
            if (! Schema::hasIndex('service_provider_original_models', 'idx_type_org_id')) {
                $table->index(['type', 'organization_code', 'model_id'], 'idx_type_org_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
