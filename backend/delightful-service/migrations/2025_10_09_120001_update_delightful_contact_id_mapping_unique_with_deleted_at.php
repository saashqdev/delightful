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
            // adjust唯oneindexbycontain deleted_at,avoid软deleterecord造become唯oneconstraintconflict
            $table->dropIndex('unique_env_origin_mapping_type_third_type_org_code');
            $table->unique(
                ['delightful_environment_id', 'origin_id', 'mapping_type', 'third_platform_type', 'delightful_organization_code', 'deleted_at'],
                'unique_env_origin_mapping_type_third_type_org_code'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_contact_third_platform_id_mapping', static function (Blueprint $table) {
            // also原fornotcontain deleted_at 唯oneindex
            $table->dropIndex('unique_env_origin_mapping_type_third_type_org_code');
            $table->unique(
                ['delightful_environment_id', 'origin_id', 'mapping_type', 'third_platform_type', 'delightful_organization_code'],
                'unique_env_origin_mapping_type_third_type_org_code'
            );
        });
    }
};
