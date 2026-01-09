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
        Schema::table('delightful_modes', function (Blueprint $table) {
            $table->json('placeholder_i18n')->nullable()->comment('modeplaceholderinternationalization')->after('name_i18n');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_modes', function (Blueprint $table) {
            $table->dropColumn('placeholder_i18n');
        });
    }
};
