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
        Schema::table('delightful_bots', function (Blueprint $table) {
            $table->index(['flow_code']);
            $table->index(['created_uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_bots', function (Blueprint $table) {
            $table->dropIndex(['flow_code']);
            $table->dropIndex(['created_uid']);
        });
    }
};
