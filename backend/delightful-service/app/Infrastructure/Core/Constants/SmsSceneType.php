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
     * bindhandmachine.
     */
    public const BIND_PHONE = 'bind_phone';

    /**
     * modifyhandmachine.
     */
    public const CHANGE_PHONE = 'change_phone';

    /**
     * accountnumberregister.
     */
    public const REGISTER_ACCOUNT = 'register_account';

    /**
     * accountnumberloginactivate.
     */
    public const ACCOUNT_LOGIN_ACTIVE = 'account_login_active';

    /**
     * accountnumberregisteractivate.
     */
    public const ACCOUNT_REGISTER_ACTIVE = 'account_register_active';

    /**
     * accountnumberlogin.
     */
    public const ACCOUNT_LOGIN = 'account_login';

    /**
     * accountnumberloginbindthethird-partyplatform.
     */
    public const ACCOUNT_LOGIN_BIND_THIRD_PLATFORM = 'account_login_bind_third_platform';

    /**
     * 身shareverify
     */
    public const IDENTIFY_VERIFY = 'identity_verify';
}
