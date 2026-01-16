<?php 
declare(strict_types=1);
 /** * Copyright (c) Be Delightful , Distributed under the MIT software license */
 use Hyperf\Database\Migrations\Migration;
 use Hyperf\Database\Schema\Blueprint;
 use Hyperf\Database\Schema\Schema;
 return new class extends Migration  {
 public function up(): void  {
 Schema::create('delightful_super_agent_project_member_settings', function (Blueprint $table)  {
 $table->bigIncrements('id')->comment('primary key');
 $table->string('user_id', 36)->comment('user ID');
 $table->bigInteger('project_id')->comment('project ID');
 $table->string('organization_code', 64)->comment('organization encoding');
 $table->tinyInteger('is_pinned')->default(0)->comment('whether pinned：0-no，1-yes');
 $table->timestamp('pinned_at')->Nullable()->comment('Pinwheninterval');
 $table->timestamp('last_active_at')->useCurrent()->comment('Last activewheninterval');
 $table->timestamps();
 $table->index(['user_id', 'project_id'], 'idx_user_project');
 $table->index('project_id', 'idx_project_id');
        }
);
    }
 public function down(): void  {
 Schema::dropIfExists('delightful_super_agent_project_member_settings');
    }
}
;

