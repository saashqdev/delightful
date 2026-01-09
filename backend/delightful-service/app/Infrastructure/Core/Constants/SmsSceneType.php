<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Constants;

class SmsSceneType
{
    /**
     * devicelogout.
     */
    public const DEVICE_LOGOUT = 'device_logout';

    /**
     * modifypassword
     */
    public const CHANGE_PASSWORD = 'change_password';

    /**
     * bindhand机.
     */
    public const BIND_PHONE = 'bind_phone';

    /**
     * modifyhand机.
     */
    public const CHANGE_PHONE = 'change_phone';

    /**
     * 账numberregister.
     */
    public const REGISTER_ACCOUNT = 'register_account';

    /**
     * 账numberloginactivate.
     */
    public const ACCOUNT_LOGIN_ACTIVE = 'account_login_active';

    /**
     * 账numberregisteractivate.
     */
    public const ACCOUNT_REGISTER_ACTIVE = 'account_register_active';

    /**
     * 账numberlogin.
     */
    public const ACCOUNT_LOGIN = 'account_login';

    /**
     * 账numberloginbindthethird-partyplatform.
     */
    public const ACCOUNT_LOGIN_BIND_THIRD_PLATFORM = 'account_login_bind_third_platform';

    /**
     * 身shareverify
     */
    public const IDENTIFY_VERIFY = 'identity_verify';
}
