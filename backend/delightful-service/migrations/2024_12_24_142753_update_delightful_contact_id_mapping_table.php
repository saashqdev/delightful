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
        Schema::table('delightful_contact_third_platform_id_mapping', static function (Blueprint $table) {
            // delightful_environment_id
            $table->bigInteger('delightful_environment_id')->default(0)->comment('delightful_environments table id')->after('delightful_organization_code');
            $table->dropIndex('unique_origin_id_mapping_type');
            $table->dropIndex('new_id_mapping_type');
            $table->index(['new_id', 'mapping_type', 'delightful_organization_code'], 'idx_new_id_mapping_type_org_code');
            $table->unique(
                ['delightful_environment_id', 'origin_id', 'mapping_type', 'third_platform_type', 'delightful_organization_code'],
                'unique_env_origin_mapping_type_third_type_org_code'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
