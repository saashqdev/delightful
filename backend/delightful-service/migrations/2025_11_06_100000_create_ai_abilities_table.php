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
        if (Schema::hasTable('delightful_ai_abilities')) {
            return;
        }

        Schema::create('delightful_ai_abilities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 50)->comment('cancapabilityuniqueoneidentifier');
            $table->string('organization_code', 100)->default('')->comment('organizationencoding');
            $table->json('name_i18n')->comment('cancapabilityname(multiplelanguageJSONformat)');
            $table->json('description_i18n')->comment('cancapabilitydescription(multiplelanguageJSONformat)');
            $table->string('icon', 100)->nullable()->comment('graphmarkidentifier');
            $table->integer('sort_order')->default(0)->comment('sort');
            $table->tinyInteger('status')->default(1)->comment('status:0-disable,1-enable');
            $table->json('config')->comment('configurationinformation(provider_code, access_point, api_key, model_idetc)');
            $table->timestamps();

            $table->index('code', 'idx_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_ai_abilities');
    }
};
