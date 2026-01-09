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
        if (Schema::hasTable('delightful_contact_id_mapping')) {
            return;
        }
        Schema::create('delightful_contact_id_mapping', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('origin_id', 255)->comment('源id');
            $table->string('new_id', 255)->comment('新id');
            // mappingtype：user id、department id、nullbetween id，organization id
            $table->string('mapping_type', 32)->comment('mappingtype（user、department、space、organization）');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['new_id', 'mapping_type'], 'new_id_mapping_type');
            $table->unique(['origin_id', 'mapping_type'], 'unique_origin_id_mapping_type');
            $table->comment('department、user、organizationencoding、nullbetweenencodingetcmappingclose系record');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_contact_id_mapping');
    }
};
