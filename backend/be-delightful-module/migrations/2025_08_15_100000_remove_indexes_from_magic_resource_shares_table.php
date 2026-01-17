<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

/*
 * Remove unnecessary indexes from delightful_resource_shares table.
 */
return new class extends Migration {
    /**
     * Run the migration.
     */
    public function up(): void
    {
        if (! Schema::hasTable('delightful_resource_shares')) {
            return;
        }

        Schema::table('delightful_resource_shares', function (Blueprint $table) {
            // Check and drop indexes if they exist
            if (Schema::hasIndex('delightful_resource_shares', 'delightful_resource_shares_created_at_index')) {
                $table->dropIndex('delightful_resource_shares_created_at_index');
            }

            if (Schema::hasIndex('delightful_resource_shares', 'delightful_resource_shares_created_uid_organization_code_index')) {
                $table->dropIndex('delightful_resource_shares_created_uid_organization_code_index');
            }

            if (Schema::hasIndex('delightful_resource_shares', 'delightful_resource_shares_expire_at_index')) {
                $table->dropIndex('delightful_resource_shares_expire_at_index');
            }

            if (Schema::hasIndex('delightful_resource_shares', 'delightful_resource_shares_share_code_unique')) {
                $table->dropUnique('delightful_resource_shares_share_code_unique');
            }
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        if (! Schema::hasTable('delightful_resource_shares')) {
            return;
        }

        Schema::table('delightful_resource_shares', function (Blueprint $table) {
            // Recreate the indexes
            $table->index('created_at', 'delightful_resource_shares_created_at_index');
            $table->index(['created_uid', 'organization_code'], 'delightful_resource_shares_created_uid_organization_code_index');
            $table->index('expire_at', 'delightful_resource_shares_expire_at_index');
            $table->unique('share_code', 'delightful_resource_shares_share_code_unique');
        });
    }
};
