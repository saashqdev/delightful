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
        if (Schema::hasTable('delightful_contact_accounts')) {
            return;
        }
        Schema::create('delightful_contact_accounts', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('delightful_id', 64)->comment('accountnumberid,cross-tenant(organization)uniqueone. foravoidanduser_id(organizationinsideuniqueone)conceptobfuscate,thereforeupnamedelightful_id')->default('');
            // accountnumbertype
            $table->tinyInteger('type')->comment('accountnumbertype,0:ai,1:personcategory')->default(0);
            // ai_code
            $table->string('ai_code', 64)->comment('aiencoding')->default('');
            // accountnumberstatus
            $table->tinyInteger('status')->comment('accountnumberstatus,0:normal,1:disable')->default(0);
            // international prefix
            $table->string('country_code', 16)->comment('international prefix')->default('');
            // handmachinenumber
            $table->string('phone', 64)->comment('handmachinenumber')->default('');
            // mailbox
            $table->string('email', 64)->comment('mailbox')->default('');
            // truename
            $table->string('real_name', 64)->comment('truename')->default('');
            // propertyother
            $table->tinyInteger('gender')->comment('propertyother,0:unknown;1:male;2:female')->default(0);
            // attachaddproperty
            $table->string('extra', 1024)->comment('attachaddproperty.')->default('');

            // indexset
            $table->index(['status', 'type'], 'idx_status_type');
            $table->unique(['delightful_id'], 'unq_delightful_id');
            $table->unique(['country_code', 'phone'], 'unq_country_code_phone');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('useraccountnumbertable,recordusercrossorganizationuniqueoneinfo,such ashandmachinenumber/truename/propertyother/usertypeetc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_contact_accounts');
    }
};
