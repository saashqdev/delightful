<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMagicFlows extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('magic_flows', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('Unique identifier');
            $table->string('code')->default('')->comment('Flow code');
            $table->string('version_code')->default('')->comment('Version code');
            $table->string('name')->default('')->comment('Flow name');
            $table->string('description')->default('')->comment('Flow description');
            $table->string('icon')->default('')->comment('Flow icon');
            $table->integer('type')->default(0)->comment('Flow type');
            $table->json('edges')->comment('Flow edges');
            $table->json('nodes')->comment('Flow nodes');
            $table->boolean('enabled')->default(true)->comment('Whether the flow is enabled');
            $table->string('organization_code')->default('')->comment('Organization code');
            $table->string('created_uid')->default('')->comment('Creator user ID');
            $table->timestamp('created_at')->nullable()->comment('Created at');
            $table->string('updated_uid')->default('')->comment('Updater user ID');
            $table->timestamp('updated_at')->nullable()->comment('Updated at');
            $table->timestamp('deleted_at')->nullable()->comment('Deleted at');

            $table->index('code');
            $table->index(['organization_code', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_flows');
    }
}
