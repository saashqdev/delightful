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
            // Add composite index for project_id, storage_type, updated_at, deleted_at
            // This index optimizes queries filtering by project, storage_type and updated_at
            $table->index(['project_id', 'storage_type', 'updated_at', 'deleted_at'], 'idx_project_storage_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
