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
        Schema::table('delightful_contact_accounts', function (Blueprint $table) {
            // for ai_code fieldaddindex
            $table->index('ai_code', 'idx_ai_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_contact_accounts', function (Blueprint $table) {
            // delete ai_code index
            $table->dropIndex('idx_ai_code');
        });
    }
};
