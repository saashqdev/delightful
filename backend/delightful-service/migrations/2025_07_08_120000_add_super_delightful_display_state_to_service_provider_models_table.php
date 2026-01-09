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
        if (! Schema::hasColumn('service_provider_models', 'be_delightful_display_state')) {
            Schema::table('service_provider_models', function (Blueprint $table) {
                $table->tinyInteger('be_delightful_display_state')->default(0)->comment('exceedslevelMagedisplayswitch:0-close,1-start');
            });
        }
    }
};
