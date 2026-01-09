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
        if (Schema::hasTable('delightful_comment_tree_indexes')) {
            return;
        }
        Schema::create('delightful_comment_tree_indexes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ancestor_id')->index()->comment('祖先sectionpointid, commentstable主keyid');
            $table->unsignedBigInteger('descendant_id')->index()->comment('back代sectionpointid, commentstable主keyid');
            $table->unsignedInteger('distance')->comment('祖先sectionpointtoback代sectionpointdistance');
            $table->string('organization_code')->index()->comment('organizationcode');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_tree_indexes');
    }
};
