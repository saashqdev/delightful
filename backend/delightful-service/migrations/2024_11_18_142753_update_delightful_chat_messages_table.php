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
        Schema::table('delightful_chat_messages', static function (Blueprint $table) {
            // byataggregatesearch存in，messagecontentmaybewillverylong，所bywillfieldtype改forlongText
            $table->longText('content')->comment('messagedetail。byataggregatesearch存in，messagecontentmaybewillverylong，所bywillfieldtype改forlongText')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_chat_messages', function (Blueprint $table) {
        });
    }
};
