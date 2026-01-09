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
        // willshowhave 'accepted' statusupdatefor 'active' status
        Db::table('delightful_long_term_memories')
            ->where('status', 'accepted')
            ->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // rollback:will 'active' statuschangereturn 'accepted' status
        Db::table('delightful_long_term_memories')
            ->where('status', 'active')
            ->update(['status' => 'accepted']);
    }
};
