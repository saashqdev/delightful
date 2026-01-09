<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Core\HighAvailability\Entity\ValueObject\CircuitBreakerStatus;
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // table存inthennotexecute
        if (Schema::hasTable('delightful_api_premium_endpoints')) {
            return;
        }

        Schema::create('delightful_api_premium_endpoints', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 255)->comment('接入pointtype。userneedfrom己guaranteenotandotherbusinessduplicate');
            $table->string('provider', 255)->comment('提供quotient')->nullable();
            $table->string('name', 255)->comment('接入pointname');
            $table->text('config')->comment('letuserfrom己存onetheseconfigurationinfo')->nullable();
            $table->tinyInteger('enabled')->default(1)->comment('whetherenable: 1=enable, 0=disable');
            $table->string('circuit_breaker_status', 32)
                ->default(CircuitBreakerStatus::CLOSED->value)
                ->comment('circuit breakstatus: closed=normalservicemiddle, open=circuit breakmiddle, half_open=尝试restoremiddle');
            $table->string('resources', 255)->comment('resourceconsume id list，onetimerequestmaybeconsume多typeresource')->nullable();
            $table->datetimes();
            $table->unique(['enabled', 'type', 'provider', 'name'], 'unique_enabled_type_provider_name');
            $table->comment('API接入pointtable，associate接入pointcanconsumeresourceinfo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_premium_endpoints');
    }
};
