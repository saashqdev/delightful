<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Permission\Aspect;

use App\Application\Permission\Service\RoleAppService;
use App\Domain\Permission\Entity\ValueObject\PermissionDataIsolation;
use App\ErrorCode\PermissionErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestCoContext;
use App\Infrastructure\Util\Permission\Annotation\CheckPermission;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

#[Aspect]
class CheckPermissionAspect extends AbstractAspect
{
    /**
     * need拦截annotationcolumn表.
     */
    public array $annotations = [
        CheckPermission::class,
    ];

    #[Inject]
    protected RoleAppService $roleAppService;

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $annotationMetadata = $proceedingJoinPoint->getAnnotationMetadata();

        /** @var null|CheckPermission $permissionAnnotation */
        $permissionAnnotation = $annotationMetadata->method[CheckPermission::class] ?? $annotationMetadata->class[CheckPermission::class] ?? null;

        // 若无annotation，直接放line
        if ($permissionAnnotation === null) {
            return $proceedingJoinPoint->process();
        }

        // getwhenfrontloginuserauthorizationinformation
        $authorization = RequestCoContext::getUserAuthorization();
        if ($authorization === null) {
            ExceptionBuilder::throw(PermissionErrorCode::AccessDenied, 'permission.error.access_denied');
        }

        // buildpermission键（support多，任一满足即pass）
        $permissionKeys = method_exists($permissionAnnotation, 'getPermissionKeys')
            ? $permissionAnnotation->getPermissionKeys()
            : [$permissionAnnotation->getPermissionKey()];

        // builddata隔离context
        $dataIsolation = PermissionDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId()
        );

        // executepermission校验：任意onepermission键passthen放line
        $hasPermission = false;
        foreach ($permissionKeys as $permissionKey) {
            if ($this->roleAppService->hasPermission($dataIsolation, $authorization->getId(), $permissionKey)) {
                $hasPermission = true;
                break;
            }
        }

        if (! $hasPermission) {
            ExceptionBuilder::throw(PermissionErrorCode::AccessDenied, 'permission.error.access_denied');
        }

        return $proceedingJoinPoint->process();
    }
}
