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
            $table->tinyInteger('type')->comment('type,for examplecomment,autostate');
            $table->json('attachments')->comment('attachment');
            $table->string('description')->comment('tocommentsimpleshortdescription,mainisgiveautostateuse,for examplecreatetodo,uploadimageetcsystemautostate');
            $table->unsignedBigInteger('resource_id')->index()->comment('commentresourceid,for exampleclouddocumentid,sheettableid');
            $table->tinyInteger('resource_type')->comment('commentresourcetype,for exampleclouddocument,sheettable');
            $table->unsignedBigInteger('parent_id')->index()->comment('parentlevelcommentprimary keyid');
            $table->text('message')->comment('commentcontent');
            $table->string('creator')->index()->comment('createperson');
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
