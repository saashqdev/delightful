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
        Schema::table('delightful_be_agent_task_files', function (Blueprint $table) {
            $table->text('metadata')
                ->nullable()
                ->after('source')
                ->comment('File metadata, stored as JSON');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_be_agent_task_files', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
