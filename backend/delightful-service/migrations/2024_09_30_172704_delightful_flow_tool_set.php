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
        Schema::create('delightful_flow_tool_sets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code', 32)->comment('organizationencoding');
            $table->string('code', 80)->comment('toolcollectionencoding');
            $table->string('name', 64)->comment('toolcollectionname');
            $table->string('description', 255)->comment('toolcollectiondescription');
            $table->string('icon', 255)->comment('toolcollectiongraphmark');
            $table->boolean('enabled')->default(true)->comment('whetherenable');
            $table->string('created_uid', 80)->comment('createperson');
            $table->dateTime('created_at')->comment('creation time');
            $table->string('updated_uid', 80)->comment('modifyperson');
            $table->dateTime('updated_at')->comment('modification time');
            $table->softDeletes();

            $table->unique(['organization_code', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
