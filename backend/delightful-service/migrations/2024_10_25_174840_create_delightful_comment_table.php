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
            $table->tinyInteger('type')->comment('type,for examplecomment,动state');
            $table->json('attachments')->comment('attachment');
            $table->string('description')->comment('tocomment简shortdescription,mainisgive动stateuse,for examplecreate待办,uploadimageetcsystem动state');
            $table->unsignedBigInteger('resource_id')->index()->comment('commentresourceid,for example云documentid,sheettableid');
            $table->tinyInteger('resource_type')->comment('commentresourcetype,for example云document,sheettable');
            $table->unsignedBigInteger('parent_id')->index()->comment('父levelcommentprimary keyid');
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
