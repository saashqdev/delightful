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
        if (! Schema::hasTable('delightful_organizations')) {
            return;
        }

        Schema::table('delightful_organizations', function (Blueprint $table) {
            // 席位quantity
            if (! Schema::hasColumn('delightful_organizations', 'seats')) {
                $table->unsignedInteger('seats')->default(0)->comment('席位quantity')->after('number');
            }

            // 同相关field
            if (! Schema::hasColumn('delightful_organizations', 'sync_type')) {
                $table->string('sync_type', 32)->default('')->comment('同type')->after('seats');
            }
            if (! Schema::hasColumn('delightful_organizations', 'sync_status')) {
                $table->tinyInteger('sync_status')->default(0)->comment('同status')->after('sync_type');
            }
            if (! Schema::hasColumn('delightful_organizations', 'sync_time')) {
                $table->timestamp('sync_time')->nullable()->comment('同时间')->after('sync_status');
            }

            // 索引：type（organizationtype）
            $table->index('type', 'idx_delightful_org_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('delightful_organizations')) {
            return;
        }

        Schema::table('delightful_organizations', function (Blueprint $table) {
            // 先delete索引
            try {
                $table->dropIndex('idx_delightful_org_sync');
            } catch (Throwable) {
            }

            try {
                $table->dropIndex('idx_delightful_org_type');
            } catch (Throwable) {
            }

            // deletefield
            if (Schema::hasColumn('delightful_organizations', 'sync_time')) {
                $table->dropColumn('sync_time');
            }
            if (Schema::hasColumn('delightful_organizations', 'sync_status')) {
                $table->dropColumn('sync_status');
            }
            if (Schema::hasColumn('delightful_organizations', 'sync_type')) {
                $table->dropColumn('sync_type');
            }
            if (Schema::hasColumn('delightful_organizations', 'seats')) {
                $table->dropColumn('seats');
            }
        });
    }
};
