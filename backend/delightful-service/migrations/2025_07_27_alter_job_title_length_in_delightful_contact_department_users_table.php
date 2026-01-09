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
        // Ensure the table exists before attempting to modify it
        if (! Schema::hasTable('delightful_contact_department_users')) {
            return;
        }

        Schema::table('delightful_contact_department_users', static function (Blueprint $table) {
            // Increase the job_title column length from 64 to 256
            $table->string('job_title', 256)
                ->comment('inthisdepartment职位')
                ->default('')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the job_title column length back to 64
        if (! Schema::hasTable('delightful_contact_department_users')) {
            return;
        }

        Schema::table('delightful_contact_department_users', static function (Blueprint $table) {
            $table->string('job_title', 64)
                ->comment('inthisdepartment职位')
                ->default('')
                ->change();
        });
    }
};
