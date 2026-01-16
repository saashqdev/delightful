<?php 
declare(strict_types=1);
 /** * Copyright (c) Be Delightful , Distributed under the MIT software license */
 use Hyperf\Database\Migrations\Migration;
 use Hyperf\Database\Schema\Blueprint;
 use Hyperf\Database\Schema\Schema;
 /**FileSortFunctionData database Optimizemigrate * addSortRelatedCompositeIndexAndDataInitialize*/
 return new class extends Migration  {
 /** * Run the migrations. */
 public function up(): void  {
 // 1. addCompositeindexwithOptimizeSortQueryPerformance Schema::table('delightful_super_agent_task_files', function (Blueprint $table) { // asProjectFileQueryaddCompositeindex (project_id, parent_id, sort, file_id) //  this indexConvertbigbigEnhance by ProjectandparentDirectoryGroup'sSortQueryPerformance $table->index(['project_id', 'parent_id', 'sort', 'file_id'], 'idx_project_parent_sort'); // asTopicFileQueryaddCompositeindex (topic_id, parent_id, sort, file_id) //  this indexConvertOptimize by TopicandparentDirectoryGroup'sSortQueryPerformance $table->index(['topic_id', 'parent_id', 'sort', 'file_id'], 'idx_topic_parent_sort'); }); } /** * Reverse the migrations. */ public function down(): void { Schema::table('delightful_super_agent_task_files', function (Blueprint $table) { // Deleteadd'sindex $table->dropIndex('idx_project_parent_sort'); $table->dropIndex('idx_topic_parent_sort'); }); } }; 

