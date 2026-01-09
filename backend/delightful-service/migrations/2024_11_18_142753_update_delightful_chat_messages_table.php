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
            // 由于聚合search的存在，messagecontent可能will很长，所以将字段type改为longText
            $table->longText('content')->comment('messagedetail。由于聚合search的存在，messagecontent可能will很长，所以将字段type改为longText')->change();
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
