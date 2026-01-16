<?php 
declare(strict_types=1);
 /** * Copyright (c) Be Delightful , Distributed under the MIT software license */
 use Hyperf\Database\Migrations\Migration;
 use Hyperf\Database\Schema\Blueprint;
 use Hyperf\Database\Schema\Schema;
 return new class extends Migration  {
 /** * Run the migrations. */
 public function up(): void  {
 Schema::table('delightful_super_agent_workspace_versions', function (Blueprint $table)  {
 // as id fieldaddUniqueindex $table->unique('id', 'idx_unique_id'); // singlecolumnindex $table->index('topic_id', 'idx_topic_id'); // Compositeindex: OverwriteMultiple typesQueryScenario (project_id, folder, commit_hash) $table->index(['project_id', 'folder', 'commit_hash'], 'idx_project_folder_commit'); }); } /** * Reverse the migrations. */ public function down(): void { Schema::table('delightful_super_agent_workspace_versions', function (Blueprint $table) { // DeleteCompositeindex $table->dropIndex('idx_project_folder_commit'); // Deletesinglecolumnindex $table->dropIndex('idx_topic_id'); // DeleteUniqueindex $table->dropUnique('idx_unique_id'); }); } }; 

