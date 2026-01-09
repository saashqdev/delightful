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
        Schema::create('delightful_organizations_environment', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('login_code', 32)->comment('logincode,useatassociateorganizationandenvironment,caninlogino clockhandautofill in.lengthmoreshort,convenientatmemory');
            $table->string('delightful_organization_code', 32)->comment('Magicorganization code');
            $table->string('origin_organization_code', 32)->comment('originalorganization code');
            // environmentid
            $table->unsignedBigInteger('environment_id')->comment('delightful_environmenttableid.tableclearthisorganizationwantusewhichenvironment');
            $table->unique('login_code', 'idx_login_code');
            $table->unique('delightful_organization_code', 'idx_delightful_organization_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_organizations_environment');
    }
};
