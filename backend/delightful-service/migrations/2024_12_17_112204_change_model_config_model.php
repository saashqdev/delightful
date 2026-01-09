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
        Schema::table('delightful_api_model_configs', function (Blueprint $table) {
            $table->string('model', 80)->default('')->comment('model')->index()->change();
            $table->string('name', 80)->default('')->comment('customizename');
            $table->boolean('enabled')->default(1)->comment('是否enable');
            $table->string('implementation', 100)->default('')->comment('implement类');
            $table->text('implementation_config')->nullable()->comment('implement类configuration');
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
