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
        if (Schema::hasTable('delightful_attachments')) {
            return;
        }
        Schema::create('delightful_attachments', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('target_id');
            $table->unsignedTinyInteger('target_type')->comment('0-unknown1-待办');
            $table->string('uid', 64);
            $table->text('key');
            $table->text('name');
            $table->unsignedTinyInteger('origin_type')->comment('uploadcomesource:0-no1-imagegroupitem2-filegroupitem')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->string('organization_code')->index()->comment('organizationcode');

            $table->index(['target_id', 'target_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
