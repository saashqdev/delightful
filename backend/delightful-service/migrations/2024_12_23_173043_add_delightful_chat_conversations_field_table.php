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
        Schema::table('delightful_chat_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('delightful_chat_conversations', 'translate_config')) {
                return;
            }
            $table->json('translate_config')->nullable()->comment('翻译configurationitem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
        });
    }
};
