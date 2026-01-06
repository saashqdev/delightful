<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

class CreateDelightfulApiAccessTokenTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delightful_api_access_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('access_token')->comment('accessToken');
            $table->string('name')->comment('Name');
            $table->string('models')->comment('Model IDs, multiple separated by comma')->nullable();
            $table->string('ip_limit')->comment('IP restrictions, multiple separated by comma')->nullable();
            $table->timestamp('expire_time')->comment('Expiration time')->nullable();
            $table->unsignedDecimal('total_amount', 40, 6)->comment('Total usage amount');
            $table->unsignedDecimal('use_amount', 40, 6)->comment('Used amount')->default(0);
            $table->string('organization_code')->comment('Organization ID');
            $table->string('user_id')->comment('User ID');
            $table->timestamp('created_at')->default(Db::raw('CURRENT_TIMESTAMP'))->comment('Created at');
            $table->timestamp('updated_at')->default(Db::raw('CURRENT_TIMESTAMP'))->comment('Updated at')->nullable();
            $table->timestamp('deleted_at')->comment('Soft delete')->nullable();
            $table->string('creator')->comment('Creator')->nullable();
            $table->string('modifier')->comment('Modifier');
            // Unique key index
            $table->unique('access_token');
            // Following indexes are for data statistics
            // user_id index
            $table->index('user_id');
            // creator index
            $table->index('creator');
            // organization_code index
            $table->index('organization_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_access_token');
    }
}
