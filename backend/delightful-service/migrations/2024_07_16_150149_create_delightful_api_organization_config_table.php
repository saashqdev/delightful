<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

class CreateDelightfulApiOrganizationConfigTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delightful_api_organization_configs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code')->comment('organizationencoding');
            $table->unsignedDecimal('total_amount', 40, 6)->comment('总quota');
            $table->unsignedDecimal('use_amount', 40, 6)->comment('usequota')->default(0);
            $table->timestamp('created_at')->default(Db::raw('CURRENT_TIMESTAMP'))->comment('createtime');
            $table->timestamp('updated_at')->default(Db::raw('CURRENT_TIMESTAMP'))->comment('modifytime')->nullable();
            $table->timestamp('deleted_at')->comment('逻辑delete')->nullable();
            // rpm
            $table->unsignedInteger('rpm')->comment('RPM限stream')->default(5000);
            $table->unique(['organization_code'], 'idx_organization');
        });
    }

    /**
     * php bin/hyperf.php gen:migration create_delightful_api_msg_log_table
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_organization_config');
    }
}
