<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

class UpdateIndexesAndComments extends Migration
{
    public function up(): void
    {
        // 清null delightful_api_premium_endpoint_statistics 表data
        Db::table('delightful_api_premium_endpoint_statistics')->truncate();

        // modify delightful_api_premium_endpoint_statistics 表 statistics_level comment
        Schema::table('delightful_api_premium_endpoint_statistics', function (Blueprint $table) {
            $table->integer('statistics_level')->comment('statisticslevel别：0-secondlevel，1-minute钟level，2-hourlevel，3-daylevel')->change();
            // modify statistics_time datatypefor datetime
            $table->dateTime('statistics_time')->change();
        });

        // modify delightful_api_premium_endpoint_responses 表索引
        Schema::table('delightful_api_premium_endpoint_responses', function (Blueprint $table) {
            // delete旧索引
            if (Schema::hasIndex('delightful_api_premium_endpoint_responses', 'endpoint_id_created_at_index')) {
                $table->dropIndex('endpoint_id_created_at_index');
            }
            if (Schema::hasIndex('delightful_api_premium_endpoint_responses', 'request_id_index')) {
                $table->dropIndex('request_id_index');
            }
            // add新索引
            $table->index(['created_at', 'endpoint_id'], 'endpoint_id_created_at_index');
        });

        // modify delightful_api_premium_endpoint_statistics 表索引
        Schema::table('delightful_api_premium_endpoint_statistics', function (Blueprint $table) {
            // delete旧索引
            if (Schema::hasIndex('delightful_api_premium_endpoint_statistics', 'unique_endpoint_id_statistics_level_time')) {
                $table->dropIndex('unique_endpoint_id_statistics_level_time');
            }

            // add新索引
            $table->unique(['statistics_time', 'statistics_level', 'endpoint_id'], 'unique_statistics_time');
        });
    }

    public function down(): void
    {
    }
}
