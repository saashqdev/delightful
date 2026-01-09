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
        Schema::create('delightful_long_term_memories', function (Blueprint $table) {
            $table->string('id', 36)->primary()->comment('memoryuniqueoneID');
            $table->text('content')->comment('memorycontent');
            $table->text('pending_content')->nullable()->comment('pending changemorememorycontent,etc待useracceptchangemore');
            $table->text('explanation')->nullable()->comment('memoryexplain,instructionthisitemmemoryforwhatvaluerecord');
            $table->text('origin_text')->nullable()->comment('originaltextcontent');
            $table->string('memory_type', 50)->default('manual_input')->comment('memorytype');
            $table->string('status', 20)->default('pending')->comment('memorystatus:pending-待accept, active-in effect, pending_revision-待revision');
            $table->tinyInteger('enabled')->default(0)->comment('whetherenable:0-disable,1-enable(only active statusmemorycanset)');
            $table->decimal('confidence', 3, 2)->unsigned()->default(0.8)->comment('confidencedegree(0-1)');
            $table->decimal('importance', 3, 2)->unsigned()->default(0.5)->comment('重wantproperty(0-1)');
            $table->unsignedInteger('access_count')->default(0)->comment('accesscount');
            $table->unsignedInteger('reinforcement_count')->default(0)->comment('strong化count');
            $table->decimal('decay_factor', 3, 2)->unsigned()->default(1.0)->comment('衰subtractfactor(0-1)');
            $table->json('tags')->nullable()->comment('taglist');
            $table->json('metadata')->nullable()->comment('yuandata');
            $table->string('org_id', 36)->comment('organizationID');
            $table->string('app_id', 36)->comment('applicationID');
            $table->string('project_id', 36)->nullable()->default(null)->comment('projectID');
            $table->string('user_id', 36)->comment('userID');
            $table->timestamp('last_accessed_at')->nullable()->comment('mostbackaccesstime');
            $table->timestamp('last_reinforced_at')->nullable()->comment('mostbackstrong化time');
            $table->timestamp('expires_at')->nullable()->comment('expiretime');
            $table->timestamp('created_at')->useCurrent()->comment('createtime');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('updatetime');
            $table->softDeletes()->comment('deletetime');
            // index
            $table->index(['org_id', 'app_id', 'user_id', 'project_id', 'last_accessed_at'], 'idx_user_last_accessed');
            $table->index(['org_id', 'app_id', 'user_id', 'project_id', 'importance'], 'idx_user_importance');
            $table->index(['expires_at', 'deleted_at'], 'idx_expires_deleted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_long_term_memories');
    }
};
