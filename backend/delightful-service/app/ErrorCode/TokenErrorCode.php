<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\ErrorCode;

use App\Infrastructure\Core\Exception\Annotation\ErrorMessage;

/**
 * range:9000, 10000.
 */
enum TokenErrorCode: int
{
    // tokennotexistsin
    #[ErrorMessage(message: 'token.not_found')]
    case TokenNotFound = 9000;

    // tokenexpire
    #[ErrorMessage(message: 'token.expired')]
    case TokenExpired = 9001;

    // tokentypenotcorrect
    #[ErrorMessage(message: 'token.type_error')]
    case TokenTypeError = 9002;

    // nothavedetecttoTokenassociatedata
    #[ErrorMessage(message: 'token.relation_not_found')]
    case TokenRelationNotFound = 9003;

    // tokenmustsettingonevalid期
    #[ErrorMessage(message: 'token.expired_at_must_set')]
    case TokenExpiredAtMustSet = 9004;

    // tokenrequiredassociateonevalue
    #[ErrorMessage(message: 'token.relation_value_must_set')]
    case TokenRelationValueMustSet = 9005;

    // tokennotuniqueone
    #[ErrorMessage(message: 'token.not_unique')]
    case TokenNotUnique = 9006;

    // tokentypeexception
    #[ErrorMessage(message: 'token.type_exception')]
    case TokenTypeException = 9007;
}
