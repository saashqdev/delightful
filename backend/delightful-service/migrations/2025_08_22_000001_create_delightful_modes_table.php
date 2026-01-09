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
            $table->json('name_i18n')->comment('modetypenameinternationalization');
            $table->string('identifier', 50)->default('')->comment('modetypeidentifier,uniqueone');
            $table->string('icon', 255)->default('')->comment('modetypegraphmark');
            $table->string('color', 10)->default('')->comment('modetypecolor');
            $table->bigInteger('sort')->default(0)->comment('sort');
            $table->text('description')->comment('modetypedescription');
            $table->tinyInteger('is_default')->default(0)->comment('whetherdefaultmodetype 0:no 1:is');
            $table->tinyInteger('status')->default(1)->comment('status 0:disable 1:enable');
            $table->tinyInteger('distribution_type')->default(1)->comment('minutematchmethod 1:customizeconfiguration 2:followothermodetype');
            $table->bigInteger('follow_mode_id')->unsigned()->default(0)->comment('followmodetypeID,0tableshownotfollow');
            $table->json('restricted_mode_identifiers')->comment('limitmodetypeidentifierarray');
            $table->string('organization_code', 32)->default('')->comment('organizationcode');
            $table->string('creator_id', 64)->default('')->comment('createpersonID');
            $table->timestamps();
            $table->softDeletes();

            // adduniqueoneindex
            $table->unique('identifier', 'idx_identifier');
        });

        // insertdefaultmodetypedata
        $this->insertDefaultModeData();
    }

    /**
     * insertdefaultmodetypedata.
     */
    private function insertDefaultModeData(): void
    {
        $defaultModeData = [
            'id' => IdGenerator::getSnowId(),
            'name_i18n' => json_encode([
                'zh_CN' => 'defaultmodetype',
                'en_US' => 'Default Mode',
            ]),
            'identifier' => 'default',
            'icon' => '',
            'sort' => 0,
            'color' => '#6366f1',
            'description' => 'onlyuseatcreateo clockinitializemodetypeandresetmodetypemiddleconfiguration',
            'is_default' => 1,
            'status' => 1,
            'distribution_type' => 1, // independentconfiguration
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
