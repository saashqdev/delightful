<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

use function Hyperf\Support\now;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('delightful_modes')) {
            return;
        }

        Schema::create('delightful_modes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->json('name_i18n')->comment('模式name国际化');
            $table->string('identifier', 50)->default('')->comment('模式标识，唯一');
            $table->string('icon', 255)->default('')->comment('模式图标');
            $table->string('color', 10)->default('')->comment('模式颜色');
            $table->bigInteger('sort')->default(0)->comment('sort');
            $table->text('description')->comment('模式description');
            $table->tinyInteger('is_default')->default(0)->comment('是否default模式 0:否 1:是');
            $table->tinyInteger('status')->default(1)->comment('status 0:禁用 1:启用');
            $table->tinyInteger('distribution_type')->default(1)->comment('分配方式 1:customizeconfiguration 2:跟随其他模式');
            $table->bigInteger('follow_mode_id')->unsigned()->default(0)->comment('跟随的模式ID，0table示不跟随');
            $table->json('restricted_mode_identifiers')->comment('限制的模式标识array');
            $table->string('organization_code', 32)->default('')->comment('organizationcode');
            $table->string('creator_id', 64)->default('')->comment('create人ID');
            $table->timestamps();
            $table->softDeletes();

            // 添加唯一索引
            $table->unique('identifier', 'idx_identifier');
        });

        // 插入default模式data
        $this->insertDefaultModeData();
    }

    /**
     * 插入default模式data.
     */
    private function insertDefaultModeData(): void
    {
        $defaultModeData = [
            'id' => IdGenerator::getSnowId(),
            'name_i18n' => json_encode([
                'zh_CN' => 'default模式',
                'en_US' => 'Default Mode',
            ]),
            'identifier' => 'default',
            'icon' => '',
            'sort' => 0,
            'color' => '#6366f1',
            'description' => '仅用于create时initialize模式及reset模式中的configuration',
            'is_default' => 1,
            'status' => 1,
            'distribution_type' => 1, // 独立configuration
            'follow_mode_id' => 0,
            'restricted_mode_identifiers' => json_encode([]),
            'organization_code' => '',
            'creator_id' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        Db::table('delightful_modes')->insert($defaultModeData);
    }
};
