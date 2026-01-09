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
        Schema::table('delightful_flow_ai_models', function (Blueprint $table) {
            $table->boolean('support_multi_modal')->default(true)->comment('whethersupport多模state')->after('support_embedding');
            $table->bigInteger('max_tokens')->default(0)->comment('mostbigtoken数')->after('vector_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
        });
    }
};
