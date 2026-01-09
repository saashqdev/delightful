<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Kernel;

use HyperfTest\Cases\Api\AbstractHttpTest;

/**
 * @internal
 * @coversNothing
 */
class PlatformSettingsApiTest extends AbstractHttpTest
{
    private string $getUrl = '/api/v1/platform/setting';

    private string $putUrl = '/api/v1/platform/setting';

    public function testShowDefaultPlatformSettings(): void
    {
        $response = $this->get($this->getUrl, [], $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $data = $response['data'];

        // verifyresponsestructure
        $this->assertArrayHasKey('logo', $data);
        $this->assertArrayHasKey('favicon', $data);
        $this->assertArrayHasKey('default_language', $data);
        $this->assertIsArray($data['logo']);
        $this->assertIsArray($data['favicon']);
        $this->assertIsString($data['default_language']);
    }

    public function testUpdatePlatformSettingsWithAllFields(): void
    {
        $payload = [
            'logo_zh_url' => 'https://example.com/logo_zh.png',
            'logo_en_url' => 'https://example.com/logo_en.png',
            'favicon_url' => 'https://example.com/favicon.ico',
            'default_language' => 'en_US',
            'name_i18n' => [
                'zh_CN' => 'testplatform',
                'en_US' => 'Test Platform',
            ],
            'title_i18n' => [
                'zh_CN' => 'testplatformtitle',
                'en_US' => 'Test Platform Title',
            ],
            'keywords_i18n' => [
                'zh_CN' => 'AI,test',
                'en_US' => 'AI,Test',
            ],
            'description_i18n' => [
                'zh_CN' => 'thisisonetestplatform',
                'en_US' => 'This is a test platform',
            ],
        ];

        $response = $this->put($this->putUrl, $payload, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $data = $response['data'];

        // verifyresponsestructure
        $this->assertArrayHasKey('logo', $data);
        $this->assertArrayHasKey('favicon', $data);
        $this->assertArrayHasKey('default_language', $data);

        // verify logo
        $this->assertArrayHasKey('zh_CN', $data['logo']);
        $this->assertArrayHasKey('en_US', $data['logo']);
        $this->assertSame('https://example.com/logo_zh.png', $data['logo']['zh_CN']['url']);
        $this->assertSame('https://example.com/logo_en.png', $data['logo']['en_US']['url']);

        // verify favicon
        $this->assertSame('https://example.com/favicon.ico', $data['favicon']['url']);

        // verifyotherfield
        $this->assertSame('en_US', $data['default_language']);
        $this->assertArrayEquals($payload['name_i18n'], $data['name_i18n'], 'name_i18n notmatch');
        $this->assertArrayEquals($payload['title_i18n'], $data['title_i18n'], 'title_i18n notmatch');
        $this->assertArrayEquals($payload['keywords_i18n'], $data['keywords_i18n'], 'keywords_i18n notmatch');
        $this->assertArrayEquals($payload['description_i18n'], $data['description_i18n'], 'description_i18n notmatch');

        // againtime GET verifypersistence
        $getResponse = $this->get($this->getUrl, [], $this->getCommonHeaders());
        $this->assertSame(1000, $getResponse['code']);
        $getData = $getResponse['data'];
        $this->assertSame('https://example.com/logo_zh.png', $getData['logo']['zh_CN']['url']);
        $this->assertSame('https://example.com/logo_en.png', $getData['logo']['en_US']['url']);
        $this->assertSame('https://example.com/favicon.ico', $getData['favicon']['url']);
        $this->assertSame('en_US', $getData['default_language']);
    }

    public function testUpdatePlatformSettingsPartially(): void
    {
        // firstsetcompletedata
        $initialPayload = [
            'logo_zh_url' => 'https://example.com/initial_logo_zh.png',
            'logo_en_url' => 'https://example.com/initial_logo_en.png',
            'favicon_url' => 'https://example.com/initial_favicon.ico',
            'default_language' => 'zh_CN',
            'name_i18n' => [
                'zh_CN' => 'initialplatform',
                'en_US' => 'Initial Platform',
            ],
        ];
        $this->put($this->putUrl, $initialPayload, $this->getCommonHeaders());

        // 部minuteupdate:onlyupdatemiddle文 logo
        $partialPayload = [
            'logo_zh_url' => 'https://example.com/updated_logo_zh.png',
        ];
        $response = $this->put($this->putUrl, $partialPayload, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $data = $response['data'];

        // verifymiddle文 logo alreadyupdate
        $this->assertSame('https://example.com/updated_logo_zh.png', $data['logo']['zh_CN']['url']);
        // verifyEnglish logo maintainnot变
        $this->assertSame('https://example.com/initial_logo_en.png', $data['logo']['en_US']['url']);
        // verify favicon maintainnot变
        $this->assertSame('https://example.com/initial_favicon.ico', $data['favicon']['url']);
    }

    public function testUpdatePlatformSettingsWithInvalidLanguage(): void
    {
        $payload = [
            'favicon_url' => 'https://example.com/favicon.ico',
            'default_language' => 'invalid_locale', // invalidlanguage
        ];

        $response = $this->put($this->putUrl, $payload, $this->getCommonHeaders());
        // shouldreturnverifyfailerror
        $this->assertNotSame(1000, $response['code']);
    }

    public function testUpdatePlatformSettingsWithInvalidUrl(): void
    {
        // testnon https URL
        $payload = [
            'favicon_url' => 'http://example.com/favicon.ico', // non https
        ];

        $response = $this->put($this->putUrl, $payload, $this->getCommonHeaders());
        // shouldreturnverifyfailerror
        $this->assertNotSame(1000, $response['code']);
    }

    public function testUpdatePlatformSettingsWithEmptyFavicon(): void
    {
        // firstset favicon
        $initialPayload = [
            'favicon_url' => 'https://example.com/favicon.ico',
        ];
        $this->put($this->putUrl, $initialPayload, $this->getCommonHeaders());

        // tryclear favicon (pass inemptystringnotwillupdate,所bynotshouldfail)
        $payload = [
            'favicon_url' => '', // emptystring
            'default_language' => 'zh_CN',
        ];

        $response = $this->put($this->putUrl, $payload, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        // favicon shouldmaintain原value(因foremptystringnotwillupdate)
        $data = $response['data'];
        $this->assertSame('https://example.com/favicon.ico', $data['favicon']['url']);
    }

    public function testUpdatePlatformSettingsWithI18nFields(): void
    {
        $payload = [
            'favicon_url' => 'https://example.com/favicon.ico',
            'name_i18n' => [
                'zh_CN' => 'Iplatform',
                'en_US' => 'My Platform',
            ],
            'title_i18n' => [
                'zh_CN' => 'websitetitle',
                'en_US' => 'Website Title',
            ],
        ];

        $response = $this->put($this->putUrl, $payload, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $data = $response['data'];

        $this->assertArrayHasKey('name_i18n', $data);
        $this->assertArrayHasKey('title_i18n', $data);
        $this->assertArrayEquals($payload['name_i18n'], $data['name_i18n'], 'name_i18n notmatch');
        $this->assertArrayEquals($payload['title_i18n'], $data['title_i18n'], 'title_i18n notmatch');
    }

    public function testUpdatePlatformSettingsWithLogoUrls(): void
    {
        $payload = [
            'favicon_url' => 'https://example.com/favicon.ico',
            'logo_zh_url' => 'https://example.com/logo_zh_new.png',
            'logo_en_url' => 'https://example.com/logo_en_new.png',
        ];

        $response = $this->put($this->putUrl, $payload, $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $data = $response['data'];

        $this->assertArrayHasKey('logo', $data);
        $this->assertArrayHasKey('zh_CN', $data['logo']);
        $this->assertArrayHasKey('en_US', $data['logo']);
        $this->assertSame('https://example.com/logo_zh_new.png', $data['logo']['zh_CN']['url']);
        $this->assertSame('https://example.com/logo_en_new.png', $data['logo']['en_US']['url']);
    }
}
