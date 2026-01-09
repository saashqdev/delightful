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
            $table->tinyInteger('type')->comment('type，for example评论、动态');
            $table->json('attachments')->comment('附件');
            $table->string('description')->comment('对评论的简短description，主要是给动态用的，for examplecreate待办、uploadimage等system动态');
            $table->unsignedBigInteger('resource_id')->index()->comment('评论的资源id，for example云documentid、sheet表id');
            $table->tinyInteger('resource_type')->comment('评论的资源type，for example云document、sheet表');
            $table->unsignedBigInteger('parent_id')->index()->comment('父级评论的primary keyid');
            $table->text('message')->comment('评论的content');
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
