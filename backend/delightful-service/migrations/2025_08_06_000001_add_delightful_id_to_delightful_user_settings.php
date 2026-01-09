<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add delightful_id column and index if they do not exist
        if (! Schema::hasColumn('delightful_user_settings', 'delightful_id')) {
            Schema::table('delightful_user_settings', function (Blueprint $table) {
                $table->string('delightful_id', 64)->nullable()->comment('账number DelightfulId')->after('organization_code');
                $table->index(['delightful_id', 'key'], 'idx_delightful_user_settings_delightful_id_key');
            });
        }

        // Make organization_code、user_id nullable
        Schema::table('delightful_user_settings', function (Blueprint $table) {
            $table->string('organization_code', 32)->nullable()->default(null)->change();
            $table->string('user_id', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Revert organization_code、user_id back to NOT NULL
        Schema::table('delightful_user_settings', function (Blueprint $table) {
            $table->string('organization_code', 32)->default('')->nullable(false)->change();
            $table->string('user_id', 64)->nullable(false)->change();
        });

        // Remove delightful_id column and its index if they exist
        if (Schema::hasColumn('delightful_user_settings', 'delightful_id')) {
            Schema::table('delightful_user_settings', function (Blueprint $table) {
                $table->dropIndex('idx_delightful_user_settings_delightful_id_key');
                $table->dropColumn('delightful_id');
            });
        }
    }
};
