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
            if (! Schema::hasColumn('delightful_bots', 'start_page')) {
                $table->boolean('start_page')->default(false)->comment('start页switch');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_bots', function (Blueprint $table) {
            $table->dropColumn('start_page');
        });
    }
};
