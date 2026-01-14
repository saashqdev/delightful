<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\OpenDev\Endpoint\Department;

use BeDelightful\EasyDingTalk\Kernel\Exceptions\BadRequestException;
use BeDelightful\EasyDingTalk\Kernel\Exceptions\InvalidParameterException;
use BeDelightful\EasyDingTalk\OpenDev\Api\Department\DepartmentListSubApi;
use BeDelightful\EasyDingTalk\OpenDev\Api\Department\GetAllParentDepartmentByUserApi;
use BeDelightful\EasyDingTalk\OpenDev\Api\Department\GetDeptByIdApi;
use BeDelightful\EasyDingTalk\OpenDev\Endpoint\OpenDevEndpoint;
use BeDelightful\EasyDingTalk\OpenDev\Parameter\Department\GetAllParentDepartmentByUserParameter;
use BeDelightful\EasyDingTalk\OpenDev\Parameter\Department\GetDeptByIdParameter;
use BeDelightful\EasyDingTalk\OpenDev\Parameter\Department\GetSubParameter;
use BeDelightful\EasyDingTalk\OpenDev\Result\Department\DeptResult;
use GuzzleHttp\RequestOptions;

class DepartmentEndpoint extends OpenDevEndpoint
{
    /**
     * Get department list.
     * @see https://open.dingtalk.com/document/orgapp/obtain-the-department-list-v2
     * @return DeptResult[]
     * @throws BadRequestException
     * @throws InvalidParameterException
     */
    public function getSub(GetSubParameter $parameter): array
    {
        $parameter->validate();

        $api = new DepartmentListSubApi();
        $api->setOptions([
            RequestOptions::QUERY => [
                'access_token' => $parameter->getAccessToken(),
            ],
            RequestOptions::JSON => [
                'dept_id' => $parameter->getDeptId(),
                'language' => $parameter->getLanguage(),
            ],
        ]);

        $result = $this->getResult($api);
        $list = [];
        foreach ($result as $rawData) {
            $deptResult = DepartmentFactory::createDeptResultByResult($rawData);
            $list[$deptResult->getDeptId()] = $deptResult;
        }
        return $list;
    }

    public function getAllParentDepartmentByUser(GetAllParentDepartmentByUserParameter $parameter): array
    {
        $parameter->validate();

        $api = new GetAllParentDepartmentByUserApi();
        $api->setOptions([
            RequestOptions::QUERY => [
                'access_token' => $parameter->getAccessToken(),
            ],
            RequestOptions::JSON => [
                'userid' => $parameter->getUserId(),
            ],
        ]);

        $result = $this->getResult($api);
        $list = [];
        foreach ($result['parent_list'] as $rawData) {
            $result = DepartmentFactory::createAllParentDepartmentResult($rawData);
            // The last item is the current data
            if ($deptId = $result->currentDeptId()) {
                $list[$deptId] = $result;
            }
        }
        return $list;
    }

    public function getDeptById(GetDeptByIdParameter $parameter): DeptResult
    {
        $parameter->validate();

        $api = new GetDeptByIdApi();
        $api->setOptions([
            RequestOptions::QUERY => [
                'access_token' => $parameter->getAccessToken(),
            ],
            RequestOptions::JSON => [
                'dept_id' => $parameter->getDeptId(),
            ],
        ]);

        $result = $this->getResult($api);
        return DepartmentFactory::createDeptResultByResult($result);
    }
}
