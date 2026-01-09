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
        // tableexistsinthennotexecute
        if (Schema::hasTable('delightful_api_premium_exceptions')) {
            return;
        }

        Schema::create('delightful_api_premium_exceptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('exception_type', 255)->comment('exceptiontype');
            $table->boolean('can_retry')->comment('whethercanretry')->nullable();
            $table->integer('retry_max_times')->comment('retrymostbigcount')->nullable();
            $table->integer('retry_interval')->comment('retrytimebetweenseparator')->nullable();
            $table->datetimes();
            $table->comment('exceptioninformationtable,saveexceptiontype,whethercanretry,retrymostbigcount,retrytimebetweenseparator');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_premium_exceptions');
    }
};
