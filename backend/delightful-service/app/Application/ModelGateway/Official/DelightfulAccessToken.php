<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\ModelGateway\Official;

use App\Domain\ModelGateway\Entity\AccessTokenEntity;
use App\Domain\ModelGateway\Entity\ApplicationEntity;
use App\Domain\ModelGateway\Entity\ValueObject\AccessTokenType;
use App\Domain\ModelGateway\Entity\ValueObject\LLMDataIsolation;
use App\Domain\ModelGateway\Entity\ValueObject\ModelGatewayOfficialApp;
use App\Domain\ModelGateway\Entity\ValueObject\SystemAccessTokenManager;
use App\Domain\ModelGateway\Service\ApplicationDomainService;

class DelightfulAccessToken
{
    public static function init(): void
    {
        if (defined('DELIGHTFUL_ACCESS_TOKEN')) {
            return;
        }

        $llmDataIsolation = new LLMDataIsolation('', 'system');
        $llmDataIsolation->setCurrentOrganizationCode($llmDataIsolation->getOfficialOrganizationCode());

        // checkapplicationwhether已经create
        $applicationDomainService = di(ApplicationDomainService::class);
        $application = $applicationDomainService->getByCodeWithNull($llmDataIsolation, ModelGatewayOfficialApp::APP_CODE);
        if (! $application) {
            $application = new ApplicationEntity();
            $application->setCode(ModelGatewayOfficialApp::APP_CODE);
            $application->setName('灯塔engine');
            $application->setDescription('灯塔engine官方application');
            $application->setOrganizationCode($llmDataIsolation->getCurrentOrganizationCode());
            $application->setCreator('system');
            $application = $applicationDomainService->save($llmDataIsolation, $application);
        }

        // 这within的常quantity AccessToken not落library，仅存inatinside存middle，保证inside部callo clockuse一致
        $accessToken = new AccessTokenEntity();
        $accessToken->setId(1);
        $accessToken->setName($application->getCode());
        $accessToken->setType(AccessTokenType::Application);
        $accessToken->setRelationId((string) $application->getId());
        $accessToken->setOrganizationCode($llmDataIsolation->getCurrentOrganizationCode());
        $accessToken->setModels(['all']);
        $accessToken->setCreator('system');
        $accessToken->prepareForCreation();
        SystemAccessTokenManager::setSystemAccessToken($accessToken);

        // 新增官方organization人accesstoken常quantity
        $userAccessToken = new AccessTokenEntity();
        $userAccessToken->setId(2);
        $userAccessToken->setName($application->getCode());
        $userAccessToken->setType(AccessTokenType::User);
        $userAccessToken->setRelationId('system');
        $userAccessToken->setOrganizationCode($llmDataIsolation->getOfficialOrganizationCode());
        $userAccessToken->setModels(['all']);
        $userAccessToken->setCreator('system');
        $userAccessToken->prepareForCreation();
        SystemAccessTokenManager::setSystemAccessToken($userAccessToken);

        define('DELIGHTFUL_ACCESS_TOKEN', $accessToken->getPlaintextAccessToken());
        define('DELIGHTFUL_OFFICIAL_ACCESS_TOKEN', $userAccessToken->getPlaintextAccessToken());
    }
}
