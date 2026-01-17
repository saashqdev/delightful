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
        Schema::table('delightful_be_agent_message', function (Blueprint $table) {
            if (Schema::hasColumn('delightful_be_agent_message', 'mentions')) {
                return;
            }
            $table->json('mentions')->nullable()->after('attachments')->comment('提及信息');
        });

        Schema::table('delightful_be_agent_task', function (Blueprint $table) {
            if (Schema::hasColumn('delightful_be_agent_task', 'mentions')) {
                return;
            }
            $table->json('mentions')->nullable()->after('attachments')->comment('提及信息');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
