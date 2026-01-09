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
        // table存在就不execute
        if (Schema::hasTable('delightful_api_premium_endpoints')) {
            return;
        }

        Schema::create('delightful_api_premium_endpoints', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 255)->comment('接入点type。userneed自己保证不与其他业务重复');
            $table->string('provider', 255)->comment('提供商')->nullable();
            $table->string('name', 255)->comment('接入点name');
            $table->text('config')->comment('让user自己存一些configurationinfo')->nullable();
            $table->tinyInteger('enabled')->default(1)->comment('是否enable: 1=enable, 0=disable');
            $table->string('circuit_breaker_status', 32)
                ->default(CircuitBreakerStatus::CLOSED->value)
                ->comment('熔断status: closed=正常service中, open=熔断中, half_open=尝试restore中');
            $table->string('resources', 255)->comment('资源的consume id list，一次request可能consume多种资源')->nullable();
            $table->datetimes();
            $table->unique(['enabled', 'type', 'provider', 'name'], 'unique_enabled_type_provider_name');
            $table->comment('API接入点table，associate了接入点的可consume资源info');
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
