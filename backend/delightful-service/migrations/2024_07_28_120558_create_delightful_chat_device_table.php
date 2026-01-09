<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateDelightfulChatDeviceTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('delightful_chat_devices')) {
            return;
        }
        Schema::create('delightful_chat_devices', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->default(0)->comment('accountid');
            $table->tinyInteger('type')->comment('设备type,1:Android；2：IOS；3：Windows; 4：MacOS；5：Web');
            $table->string('brand', 20)->comment('手机服务商');
            $table->string('model', 20)->comment('机型');
            $table->string('system_version', 10)->comment('systemversion');
            $table->string('sdk_version', 10)->comment('appversion');
            $table->tinyInteger('status')->default(0)->comment('onlinestatus，0：offline；1：online');
            $table->string('sid', 25)->comment('connect到服务端的sid');
            $table->string('client_addr', 25)->comment('客户端地址');
            $table->index('user_id', 'idx_user_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_chat_devices');
    }
}
