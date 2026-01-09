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
        Schema::table('knowledge_base_documents', function (Blueprint $table) {
            // deleteold唯一索引
            $table->dropUnique('unique_code_version');

            // addnew唯一索引
            $table->unique(['knowledge_base_code', 'code', 'version'], 'unique_knowledge_base_code_code_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_base_documents', function (Blueprint $table) {
            // deletenew唯一索引
            $table->dropUnique('unique_code_version');

            // restoreold唯一索引
            $table->unique(['code', 'version'], 'unique_code_version');
        });
    }
};
