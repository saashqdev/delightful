<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Endpoint\User;

use BeDelightful\EasyDingTalk\Kernel\Exceptions\BadRequestException;
use BeDelightful\EasyDingTalk\Kernel\Exceptions\InvalidParameterException;
use BeDelightful\EasyDingTalk\OpenDev\Api\User\AdminListApi;
use BeDelightful\EasyDingTalk\OpenDev\Api\User\UserInfoByCodeApi;
use BeDelightful\EasyDingTalk\OpenDev\Api\User\UserInfoByMobileApi;
use BeDelightful\EasyDingTalk\OpenDev\Api\User\UserInfoByUserIdApi;
use BeDelightful\EasyDingTalk\OpenDev\Api\User\UserListApi;
use BeDelightful\EasyDingTalk\OpenDev\Endpoint\OpenDevEndpoint;
use BeDelightful\EasyDingTalk\OpenDev\Parameter\User\GetListAdminByParameter;
use BeDelightful\EasyDingTalk\OpenDev\Parameter\User\GetListByDeptIdParameter;
use BeDelightful\EasyDingTalk\OpenDev\Parameter\User\GetUserInfoByCodeParameter;
use BeDelightful\EasyDingTalk\OpenDev\Parameter\User\GetUserInfoByMobileParameter;
use BeDelightful\EasyDingTalk\OpenDev\Parameter\User\GetUserInfoByUserIdParameter;
use BeDelightful\EasyDingTalk\OpenDev\Result\User\AdminResult;
use BeDelightful\EasyDingTalk\OpenDev\Result\User\UserByCodeResult;
use BeDelightful\EasyDingTalk\OpenDev\Result\User\UserByMobileResult;
use BeDelightful\EasyDingTalk\OpenDev\Result\User\UserListResult;
use BeDelightful\EasyDingTalk\OpenDev\Result\User\UserResult;
use GuzzleHttp\RequestOptions;

class UserEndpoint extends OpenDevEndpoint
{
    /**
     * Get user information by login-free authorization code.
     * @see https://open.dingtalk.com/document/isvapp/obtain-the-user-information-based-on-the-sso-token
     * @throws BadRequestException
     * @throws InvalidParameterException
     */
    public function getUserInfoByCode(GetUserInfoByCodeParameter $parameter): UserByCodeResult
    {
        $parameter->validate();

        $api = new UserInfoByCodeApi();
        $api->setOptions([
            RequestOptions::QUERY => [
                'access_token' => $parameter->getAccessToken(),
            ],
            RequestOptions::JSON => [
                'code' => $parameter->getCode(),
            ],
        ]);
        $result = $this->getResult($api);
        return UserFactory::createUserByCodeResultByRawData($result);
    }

    /**
     * Get user information by user id.
     * @throws BadRequestException
     * @throws InvalidParameterException
     */
    public function getUserInfoByUserId(GetUserInfoByUserIdParameter $parameter): UserResult
    {
        $parameter->validate();

        $api = new UserInfoByUserIdApi();
        $api->setOptions([
            RequestOptions::QUERY => [
                'access_token' => $parameter->getAccessToken(),
            ],
            RequestOptions::JSON => [
                'userid' => $parameter->getUserId(),
            ],
        ]);
        $result = $this->getResult($api);
        return UserFactory::createUserResultByRawData($result);
    }

    /**
     * Get user information under department.
     * @see https://open.dingtalk.com/document/isvapp/queries-the-complete-information-of-a-department-user
     * @throws BadRequestException
     * @throws InvalidParameterException
     */
    public function getListByDeptId(GetListByDeptIdParameter $parameter): UserListResult
    {
        $parameter->validate();

        $api = new UserListApi();
        $api->setOptions([
            RequestOptions::QUERY => [
                'access_token' => $parameter->getAccessToken(),
            ],
            RequestOptions::JSON => [
                'dept_id' => $parameter->getDeptId(),
                'cursor' => $parameter->getCursor(),
                'size' => $parameter->getSize(),
            ],
        ]);
        $result = $this->getResult($api);
        return UserFactory::createUserListResultByRawData($result);
    }

    /**
     * Get administrator list.
     * @return AdminResult[]
     * @throws BadRequestException
     * @throws InvalidParameterException
     */
    public function getListAdmin(GetListAdminByParameter $parameter): array
    {
        $parameter->validate();
        $api = new AdminListApi();
        $api->setOptions([
            RequestOptions::QUERY => [
                'access_token' => $parameter->getAccessToken(),
            ],
        ]);
        $result = $this->getResult($api);
        $list = [];
        foreach ($result as $rawData) {
            $admin = UserFactory::createAdminResultByRawData($rawData);
            $list[$admin->getUserId()] = $admin;
        }
        return $list;
    }

    /**
     * Get user information by mobile phone number.
     * @see https://open.dingtalk.com/document/orgapp/query-users-by-phone-number
     * @throws BadRequestException
     * @throws InvalidParameterException
     */
    public function getUserIdByMobile(GetUserInfoByMobileParameter $parameter): UserByMobileResult
    {
        $parameter->validate();

        $api = new UserInfoByMobileApi();
        $api->setOptions([
            RequestOptions::QUERY => [
                'access_token' => $parameter->getAccessToken(),
            ],
            RequestOptions::JSON => [
                'mobile' => $parameter->getMobile(),
            ],
        ]);
        $result = $this->getResult($api);
        return UserFactory::createUserResultByMobileRawData($result);
    }
}
