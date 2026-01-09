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
        Schema::table('delightful_api_access_tokens', function (Blueprint $table) {
            $table->string('user_id')->default('')->comment('userid')->change();
            $table->string('type', 20)->default('user')->comment('type')->after('access_token');
            $table->string('relation_id', 255)->default('')->comment('associateID')->after('type');
            $table->string('description', 255)->default('')->comment('description');
            $table->integer('rpm')->default(0)->comment('限stream');
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
