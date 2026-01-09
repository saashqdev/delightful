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
        Schema::table('delightful_flows', function (Blueprint $table) {
            $table->json('global_variable')->nullable()->comment('all局变量')->after('nodes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
