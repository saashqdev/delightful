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
        if (Schema::hasTable('delightful_organization_admins')) {
            return;
        }
        Schema::create('delightful_organization_admins', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 64)->comment('userID,toshoulddelightful_contact_users.user_id');
            $table->string('organization_code', 64)->comment('organizationencoding');
            $table->string('delightful_id', 64)->nullable()->comment('Delightful ID');
            $table->string('grantor_user_id', 64)->nullable()->comment('authorizationpersonuserID');
            $table->timestamp('granted_at')->nullable()->comment('authorizationtime');
            $table->tinyInteger('status')->default(1)->comment('status: 0=disable, 1=enable');
            $table->tinyInteger('is_organization_creator')->default(0)->comment('whetherfororganizationcreateperson: 0=no, 1=is');
            $table->text('remarks')->nullable()->comment('note');
            $table->timestamps();
            $table->softDeletes();

            // index
            $table->index(['organization_code', 'user_id'], 'idx_organization_code_user_id');
            $table->index(['organization_code', 'is_organization_creator', 'granted_at'], 'idx_organization_code_queries');
            $table->index(['delightful_id'], 'idx_delightful_id');

            $table->comment('organizationadministratortable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_organization_admins');
    }
};
