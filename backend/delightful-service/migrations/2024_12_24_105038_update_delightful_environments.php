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
        Schema::table('delightful_environments', function (Blueprint $table) {
            $table->text('extra')->nullable()->comment('extensionfield,such asrecordonedownthisenvironmentassociateenvironment id.(预publishandproductionisassociate)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
