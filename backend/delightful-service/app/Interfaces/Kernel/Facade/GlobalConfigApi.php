<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Kernel\Facade;

use App\Application\Kernel\DTO\GlobalConfig;
use App\Application\Kernel\Service\DelightfulSettingAppService;
use App\Application\Kernel\Service\PlatformSettingsAppService;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\HttpServer\Contract\RequestInterface;
use Throwable;

#[ApiResponse('low_code')]
class GlobalConfigApi
{
    public function __construct(
        private readonly DelightfulSettingAppService $delightfulSettingAppService,
    ) {
    }

    public function getGlobalConfig(): array
    {
        $config = $this->delightfulSettingAppService->get();
        $result = $config->toArray();

        // merge平台setting
        try {
            /** @var PlatformSettingsAppService $platformSettingsAppService */
            $platformSettingsAppService = di(PlatformSettingsAppService::class);
            $platform = $platformSettingsAppService->get();
            $result = array_merge($result, self::platformSettingsToResponse($platform->toArray()));
        } catch (Throwable $e) {
            // ignore平台settingexception,avoidimpactall局configurationread
        }

        return $result;
    }

    public function updateGlobalConfig(RequestInterface $request): array
    {
        $isMaintenance = (bool) $request->input('is_maintenance', false);
        $description = (string) $request->input('maintenance_description', '');

        $config = new GlobalConfig();
        $config->setIsMaintenance($isMaintenance);
        $config->setMaintenanceDescription($description);

        $this->delightfulSettingAppService->save($config);

        return $config->toArray();
    }

    private static function platformSettingsToResponse(array $settings): array
    {
        // will logo_urls convertforfront端example结构
        $logo = [];
        foreach (($settings['logo_urls'] ?? []) as $locale => $url) {
            $logo[$locale] = $url;
        }
        $favicon = null;
        if (! empty($settings['favicon_url'] ?? '')) {
            $favicon = (string) $settings['favicon_url'];
        }
        $minimalLogo = null;
        if (! empty($settings['minimal_logo_url'] ?? '')) {
            $minimalLogo = (string) $settings['minimal_logo_url'];
        }
        $resp = [
            'logo' => $logo,
            'favicon' => $favicon,
            'minimal_logo' => $minimalLogo,
            'default_language' => (string) ($settings['default_language'] ?? 'zh_CN'),
        ];
        foreach (['name_i18n', 'title_i18n', 'keywords_i18n', 'description_i18n'] as $key) {
            if (isset($settings[$key])) {
                $resp[$key] = (array) $settings[$key];
            }
        }
        return $resp;
    }
}
