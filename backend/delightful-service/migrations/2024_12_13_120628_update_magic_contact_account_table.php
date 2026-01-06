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
        Schema::table('delightful_contact_accounts', static function (Blueprint $table) {
            // delightful_environment_id
            $table->bigInteger('delightful_environment_id')->comment('delightful_environments 表的 id')->default(0);
            $table->dropIndex('unq_country_code_phone');
            $table->index(['country_code', 'phone', 'delightful_environment_id'], 'idx_country_code_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
