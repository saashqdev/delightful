<?php 
declare(strict_types=1);
 /** * Copyright (c) Be Delightful , Distributed under the MIT software license */
 use Hyperf\Database\Migrations\Migration;
 use Hyperf\Database\Schema\Blueprint;
 use Hyperf\Database\Schema\Schema;
 return new class extends Migration  {
 /** * Run the migrations. */
 public function up(): void  {
 if (Schema::hasTable('delightful_super_agent_token_usage_records'))  {
 return;
        }
 Schema::create('delightful_super_agent_token_usage_records', function (Blueprint $table)  {
 $table->bigIncrements('id');
 $table->bigInteger('topic_id')->comment('topic ID');
 $table->string('task_id', 64)->comment('task ID');
 $table->string('sandbox_id', 64)->Nullable()->comment('sandbox ID');
 $table->string('organization_code', 64)->comment('Organization code');
 $table->string('user_id', 64)->comment('user ID');
 $table->string('task_status', 32)->comment('task status');
 $table->string('usage_type', 32)->comment(' use fortype(summary/item)');
 $table->integer('total_input_tokens')->Nullable()->default(0)->comment('totalInputtoken count ');
 $table->integer('total_output_tokens')->Nullable()->default(0)->comment('totalOutputtoken count ');
 $table->integer('total_tokens')->Nullable()->default(0)->comment('totaltoken count ');
 $table->string('model_id', 128)->Nullable()->comment('modelID');
 $table->string('model_name', 128)->Nullable()->comment('model name');
 $table->integer('cached_tokens')->Nullable()->default(0)->comment('Cachetoken count ');
 $table->integer('cache_write_tokens')->Nullable()->default(0)->comment('CacheWritetoken count ');
 $table->integer('reasoning_tokens')->Nullable()->default(0)->comment('Reasoningtoken count ');
 $table->json('usage_details')->Nullable()->comment('Complete's use forDetailsJSON'); $table->timestamps(); $table->softDeletes(); // indexDesign $table->index(['task_id', 'topic_id'], 'idx_task_topic'); $table->index(['organization_code', 'user_id', 'created_at'], 'idx_org_user_created'); $table->index(['created_at'], 'idx_created_at'); $table->index(['usage_type'], 'idx_usage_type'); }); } /** * Reverse the migrations. */ public function down(): void { Schema::dropIfExists('delightful_super_agent_token_usage_records'); } }; 
