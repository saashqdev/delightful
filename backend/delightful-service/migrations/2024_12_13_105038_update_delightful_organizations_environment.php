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
        Schema::table('delightful_organizations_environment', function (Blueprint $table) {
            $table->dropIndex('idx_login_code');
            $table->dropIndex('idx_delightful_organization_code');
            $table->unique('login_code', 'unq_login_code');
            $table->unique('delightful_organization_code', 'unq_delightful_organization_code');
            $table->unique(['environment_id', 'origin_organization_code'], 'unq_environment_organization_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
