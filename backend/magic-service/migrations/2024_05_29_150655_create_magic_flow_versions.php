<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMagicFlowVersions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('magic_flow_versions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('flow_code')->default('')->comment('Belonging flow code');
            $table->string('code')->default('')->comment('Version code');
            $table->string('name')->default('')->comment('Version name');
            $table->string('description')->default('')->comment('Version description');
            $table->json('magic_flow')->nullable(false)->comment('Flow payload');
            $table->string('organization_code')->default('')->comment('Organization code');
            $table->string('created_uid')->default('')->comment('Creator user ID');
            $table->timestamp('created_at')->nullable()->comment('Created at');
            $table->string('updated_uid')->default('')->comment('Updater user ID');
            $table->timestamp('updated_at')->nullable()->comment('Updated at');
            $table->timestamp('deleted_at')->nullable()->comment('Deleted at');

            $table->index(['flow_code', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_flow_versions');
    }
}
