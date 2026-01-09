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
        Schema::create('default_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('business_type')->comment('模piecetype,file属at哪模piece');
            $table->integer('file_type')->comment('filetype：0:官方add，1:organizationadd');
            $table->string('key')->comment('filekey');
            $table->bigInteger('file_size')->comment('filesize');
            $table->string('organization')->index()->comment('organizationencoding');
            $table->string('file_extension')->index()->comment('fileback缀');
            $table->string('user_id')->comment('upload者');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_files');
    }
};
