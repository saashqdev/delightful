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
        if (Schema::hasTable('delightful_comments')) {
            return;
        }
        Schema::create('delightful_comments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('type')->comment('type，for examplecomment、动state');
            $table->json('attachments')->comment('attachment');
            $table->string('description')->comment('对comment的简短description，main是给动stateuse的，for examplecreate待办、uploadimageetcsystem动state');
            $table->unsignedBigInteger('resource_id')->index()->comment('comment的resourceid，for example云documentid、sheet表id');
            $table->tinyInteger('resource_type')->comment('comment的resourcetype，for example云document、sheet表');
            $table->unsignedBigInteger('parent_id')->index()->comment('父levelcomment的primary keyid');
            $table->text('message')->comment('comment的content');
            $table->string('creator')->index()->comment('create人');
            $table->string('organization_code')->index()->comment('organizationcode');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment');
    }
};
