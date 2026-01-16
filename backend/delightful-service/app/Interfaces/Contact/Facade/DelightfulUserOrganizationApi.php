<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Contact\Facade;

use App\Application\Contact\Service\DelightfulUserOrganizationAppService;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\AbstractApi;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;

/**
 * usercurrentorganizationmanageAPI.
 */
#[ApiResponse('low_code')]
class DelightfulUserOrganizationApi extends AbstractApi
{
    #[Inject]
    protected DelightfulUserOrganizationAppService $userOrganizationAppService;

    #[Inject]
    protected DelightfulUserDomainService $userDomainService;

    /**
     * getusercurrentorganizationcode
     */
    public function getCurrentOrganizationCode(RequestInterface $request): array
    {
        // fromrequestheadget authorization
        $authorization = (string) $request->header('authorization', '');
        if ($authorization === '') {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR);
        }

        $delightfulId = $this->getDelightfulIdByAuthorization($authorization);

        // getcurrentuserorganizationcode
        return $this->userOrganizationAppService->getCurrentOrganizationCode($delightfulId);
    }

    /**
     * setusercurrentorganizationcode
     */
    public function setCurrentOrganizationCode(RequestInterface $request): array
    {
        // fromrequestheadget authorization
        $authorization = (string) $request->header('authorization', '');
        if ($authorization === '') {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR);
        }

        $delightfulId = $this->getDelightfulIdByAuthorization($authorization);

        // fromrequestbodygetorganizationcode
        $organizationCode = (string) $request->input('delightful_organization_code', '');
        if (empty($organizationCode)) {
            ExceptionBuilder::throw(UserErrorCode::ORGANIZATION_NOT_EXIST);
        }

        // setusercurrentorganizationcode
        return $this->userOrganizationAppService->setCurrentOrganizationCode($delightfulId, $organizationCode);
    }

    /**
     * getaccountnumberdown havecanswitchorganizationlist.
     */
    public function listOrganizations(RequestInterface $request): array
    {
        $authorization = (string) $request->header('authorization', '');
        if ($authorization === '') {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR);
        }

        return $this->userOrganizationAppService->getOrganizationsByAuthorization($authorization)->toArray();
    }

    private function getDelightfulIdByAuthorization(string $authorization): string
    {
        $userDetails = $this->userDomainService->getUsersDetailByAccountFromAuthorization($authorization);
        if (empty($userDetails)) {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR);
        }
        // sameoneaccountnumberdown delightful_id alllocally uniqueone,thiswithingetfirstimmediatelycan
        return $userDetails[0]->getDelightfulId();
    }
}
