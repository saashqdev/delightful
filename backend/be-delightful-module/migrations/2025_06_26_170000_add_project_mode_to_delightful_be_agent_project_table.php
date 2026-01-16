<?php 
declare(strict_types=1);
 /** * Copyright (c) Be Delightful , Distributed under the MIT software license */
 use Hyperf\Database\Migrations\Migration;
 use Hyperf\Database\Schema\Blueprint;
 use Hyperf\Database\Schema\Schema;
 return new class extends Migration  {
 /** * Run migrations. */
 public function up(): void  {
 // as delightful_super_agent_project tableAdd project_mode field Schema::table('delightful_super_agent_project', function (Blueprint $table) { if (Schema::hasColumn('delightful_super_agent_project', 'project_mode')) { return; } $table->string('project_mode', 50)->Nullable()->default(Null)->comment('Projectmode: general-Generalformode, ppt-PPTmode, data_analysis-dataAnalysismode, report-Research reportmode, meeting-Meetingmode, summary-Summarymode, be_delightful-Be Delightfulmode')->after('current_topic_status'); }); echo 'ForProjectTableAddProjectModeFieldComplete' . PHP_EOL; } /** * Reverse migrations. */ public function down(): void { // Delete delightful_super_agent_project table's project_mode field Schema::table('delightful_super_agent_project', function (Blueprint $table) { $table->dropColumn('project_mode'); }); echo 'DeleteProjectTable's ProjectModeFieldComplete' . PHP_EOL; } }; 

