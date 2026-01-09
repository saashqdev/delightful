<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\DbConnection\Db;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 将现have的 'accepted' statusupdate为 'active' status
        Db::table('delightful_long_term_memories')
            ->where('status', 'accepted')
            ->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 回滚：将 'active' status改回 'accepted' status
        Db::table('delightful_long_term_memories')
            ->where('status', 'active')
            ->update(['status' => 'accepted']);
    }
};
