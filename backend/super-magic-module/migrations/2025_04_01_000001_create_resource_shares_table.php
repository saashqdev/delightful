<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

/** Create resource shares table.*/
return new class extends Migration {
    /**
     * Run migrations.
     
 */
    public function up(): void
    {
        if (Schema::hasTable('magic_resource_shares')) {
            return;
        }
        Schema::create('magic_resource_shares', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('resource_id', 64)->comment('resource ID');
            $table->unsignedTinyInteger('resource_type')->comment('resource type');
            $table->string('resource_name', 255)->comment('resource name');
            $table->string('share_code', 64)->unique()->comment('share code');
            $table->unsignedTinyInteger('share_type')->comment('share type');
            $table->string('password', 64)->nullable()->comment('access password');
            $table->timestamp('expire_at')->nullable()->comment('expiration time');
            $table->unsignedInteger('view_count')->default(0)->comment('view count');
            $table->string('created_uid', 64)->default('')->comment('creator user ID');
            $table->string('updated_uid', 64)->default('')->comment('updater user ID');
            $table->string('organization_code', 64)->comment('organization code');
            $table->json('target_ids')->nullable()->comment('targetIDs');
            $table->timestamps();
            $table->softDeletes();

            // add index
            $table->index('resource_id');
            $table->index(['resource_type', 'resource_id']);
            $table->index(['created_uid', 'organization_code']);
            $table->index('created_at');
            $table->index('expire_at');
        });
    }

    /**
     * Reverse migrations.
     
 */
    public function down(): void
    {
        Schema::dropIfExists('magic_resource_shares');
    }
};
