<?php 
declare(strict_types=1);
 /** * Copyright (c) Be Delightful , Distributed under the MIT software license */
 use Hyperf\Database\Migrations\Migration;
 use Hyperf\Database\Schema\Blueprint;
 use Hyperf\Database\Schema\Schema;
 return new class extends Migration  {
 /** * Run the migrations. */
 public function up(): void  {
 Schema::table('delightful_super_agent_message', function (Blueprint $table)  {
 // ============ addqueuecolumnProcess/Handlefield ============ // addOriginalDatastoredfield $table->longText('raw_data')->Nullable()->comment('Original deliveryMessageJSON data')->after('mentions'); // addsequencecolumnIDfield, forStrictSort $table->bigInteger('seq_id')->unsigned()->Nullable()->comment('sequencecolumnID, forMessagesort order')->after('raw_data'); // addProcess/HandleStatusfield $table->string('processing_status', 20) ->default('') ->comment('MessageProcess/Handlestatus: pending-pendingProcess/Handle, processing-Process/Handlein, completed-completed, failed-failed') ->after('seq_id'); // addErrorinformationfield $table->text('error_message')->Nullable()->comment('Process/Handlefailedwhen'serror message')->after('processing_status'); // addRetry times  count field $table->tinyInteger('retry_count')->unsigned()->default(0)->comment('retry count')->after('error_message'); // addProcess/HandleCompletewheninterval $table->timestamp('processed_at')->Nullable()->comment('Process/HandleCompletewheninterval')->after('retry_count'); }); } /** * Reverse the migrations. */ public function down(): void { Schema::table('delightful_super_agent_message', function (Blueprint $table) { // Deleteindex $table->dropIndex('idx_topic_status_sender_seq'); $table->dropIndex('idx_status_seq_asc'); $table->dropIndex('idx_status_created'); $table->dropIndex('idx_status_retry_created'); $table->dropIndex('idx_seq_id'); $table->dropIndex('idx_task_status'); // Deletefield $table->dropColumn([ 'raw_data', 'seq_id', 'processing_status', 'error_message', 'retry_count', 'processed_at', ]); }); } }; 

