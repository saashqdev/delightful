<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\MCP\Entity\ValueObject\ServiceConfig;

use App\ErrorCode\MCPErrorCode;
use App\Infrastructure\Core\AbstractValueObject;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\SSRF\SSRFUtil;

class Oauth2Config extends AbstractValueObject
{
    /**
     * Client ID - customer端ID，isapplicationinauthorizationservice器middle唯一identifier。
     * authorizationservice器passcustomer端IDcome识别different三方application。
     *
     * createOAuthapplicationo clockwillminute配client_id，本exampleinput
     * 813924812101982004357116497xxxx.app.coze
     */
    protected string $clientId = '';

    /**
     * Client Secret - customer端key，andcustomer端ID配合use，useatauthapplication身share。
     * ensureonlyauthorizationapplicationcanrequestpermission。
     *
     * createOAuthapplicationo clockwillminute配client_secret，本exampleinput 8jmSATwI*********
     */
    protected string $clientSecret = '';

    /**
     * Client URL - service方OAuthpageURL，useatspliceuserloginauthorization页URL。
     *
     * userlogino clock，扣子willwilluser引导to"[client_url]?response_type=code&client_id=
     * [client_id]&scope=[scope]&state=xyz123&redirect_uri=[coze平台callbacksecurityground址]"。
     *
     * 参考service方authorizationdocumentgetclient_url，本example参考扣子openhairguidedocument，input
     * https://www.coze.cn/api/permission/oauth2/authorize
     */
    protected string $clientUrl = '';

    /**
     * Scope - allowapplication程序requestaccessuserdatarange。
     *
     * 参考service方authorizationdocumentinputscope。
     */
    protected string $scope = '';

    /**
     * Authorization URL - getuseraccess_tokenURLground址。
     *
     * userpassclient_urlauthorizationsuccessback，三方servicewillreturnusergettokencode，
     * and转tocallbackground址。此o clock，service器提供方willpassto应datatoauthorization_urlhairuprequest，
     * getuseraccess_token。
     *
     * 参考service方authorizationdocumentgetauthorization_url，本example参考扣子openhairguidedocument，input
     * https://api.coze.cn/api/permission/oauth2/token
     */
    protected string $authorizationUrl = '';

    /**
     * Authorization Content Type - toOAuth提供者senddatacontenttype。
     * 目front仅supportapplication/jsontype。
     */
    protected string $authorizationContentType = 'application/json';

    // Enhanced OAuth2/OIDC fields
    protected string $issuerUrl = '';

    protected string $redirectUri = '';

    protected bool $usePKCE = true;

    protected string $responseType = 'code';

    protected string $grantType = 'authorization_code';

    protected array $additionalParams = [];

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function setClientId(string $clientId): void
    {
        $this->clientId = $clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): void
    {
        $this->clientSecret = $clientSecret;
    }

    public function getClientUrl(): string
    {
        return $this->clientUrl;
    }

    public function setClientUrl(string $clientUrl): void
    {
        $this->clientUrl = $clientUrl;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function getAuthorizationUrl(): string
    {
        return $this->authorizationUrl;
    }

    public function setAuthorizationUrl(string $authorizationUrl): void
    {
        $this->authorizationUrl = $authorizationUrl;
    }

    public function getAuthorizationContentType(): string
    {
        return $this->authorizationContentType;
    }

    public function setAuthorizationContentType(string $authorizationContentType): void
    {
        $this->authorizationContentType = $authorizationContentType;
    }

    // Enhanced OAuth2/OIDC getters and setters
    public function getIssuerUrl(): string
    {
        return $this->issuerUrl;
    }

    public function setIssuerUrl(string $issuerUrl): void
    {
        $this->issuerUrl = $issuerUrl;
    }

    public function getRedirectUri(): string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function shouldUsePKCE(): bool
    {
        return $this->usePKCE;
    }

    public function setUsePKCE(bool $usePKCE): void
    {
        $this->usePKCE = $usePKCE;
    }

    public function getResponseType(): string
    {
        return $this->responseType;
    }

    public function setResponseType(string $responseType): void
    {
        $this->responseType = $responseType;
    }

    public function getGrantType(): string
    {
        return $this->grantType;
    }

    public function setGrantType(string $grantType): void
    {
        $this->grantType = $grantType;
    }

    public function getAdditionalParams(): array
    {
        return $this->additionalParams;
    }

    public function setAdditionalParams(array $additionalParams): void
    {
        $this->additionalParams = $additionalParams;
    }

    // Enhanced OAuth2/OIDC methods
    public function getWellKnownUrl(): string
    {
        if (empty($this->issuerUrl)) {
            return '';
        }

        return rtrim($this->issuerUrl, '/') . '/.well-known/openid_configuration';
    }

    public function toClientMetadata(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uris' => [$this->redirectUri],
            'response_types' => [$this->responseType],
            'grant_types' => [$this->grantType],
            'scope' => $this->scope,
            'token_endpoint_auth_method' => 'client_secret_post',
        ];
    }

    public function validate(): void
    {
        // Validate required fields
        $requiredFields = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'client_url' => $this->clientUrl,
            'authorization_url' => $this->authorizationUrl,
        ];

        foreach ($requiredFields as $fieldKey => $fieldValue) {
            if (empty(trim($fieldValue))) {
                ExceptionBuilder::throw(MCPErrorCode::ValidateFailed, 'common.empty', ['label' => 'mcp.fields.' . $fieldKey]);
            }
        }

        // Validate URLs
        $urls = [
            'client_url' => $this->clientUrl,
            'authorization_url' => $this->authorizationUrl,
        ];

        // Add optional URLs if they're provided
        if (! empty($this->issuerUrl)) {
            $urls['issuer_url'] = $this->issuerUrl;
        }

        if (! empty($this->redirectUri)) {
            $urls['redirect_uri'] = $this->redirectUri;
        }

        foreach ($urls as $fieldKey => $url) {
            if (! is_url($url)) {
                ExceptionBuilder::throw(MCPErrorCode::ValidateFailed, 'common.invalid', ['label' => 'mcp.fields.' . $fieldKey]);
            }
            // Validate URL for SSRF protection
            SSRFUtil::getSafeUrl($url, replaceIp: false, allowRedirect: true);
        }

        // Validate response type and grant type
        $validResponseTypes = ['code', 'token', 'id_token', 'code id_token', 'code token', 'id_token token', 'code id_token token'];
        if (! in_array($this->responseType, $validResponseTypes)) {
            ExceptionBuilder::throw(MCPErrorCode::ValidateFailed, 'common.invalid', ['label' => 'mcp.fields.response_type']);
        }

        $validGrantTypes = ['authorization_code', 'implicit', 'password', 'client_credentials', 'refresh_token'];
        if (! in_array($this->grantType, $validGrantTypes)) {
            ExceptionBuilder::throw(MCPErrorCode::ValidateFailed, 'common.invalid', ['label' => 'mcp.fields.grant_type']);
        }

        // Validate authorization content type - only support application/json
        if ($this->authorizationContentType !== 'application/json') {
            ExceptionBuilder::throw(MCPErrorCode::ValidateFailed, 'common.invalid', ['label' => 'mcp.fields.authorization_content_type']);
        }
    }

    public static function fromArray(array $array): self
    {
        $instance = new self();
        $instance->setClientId($array['client_id'] ?? '');
        $instance->setClientSecret($array['client_secret'] ?? '');
        $instance->setClientUrl($array['client_url'] ?? '');
        $instance->setScope($array['scope'] ?? '');
        $instance->setAuthorizationUrl($array['authorization_url'] ?? '');
        $instance->setAuthorizationContentType($array['authorization_content_type'] ?? 'application/json');

        // Enhanced OAuth2/OIDC fields
        $instance->setIssuerUrl($array['issuer_url'] ?? '');
        $instance->setRedirectUri($array['redirect_uri'] ?? '');
        $instance->setUsePKCE($array['use_pkce'] ?? true);
        $instance->setResponseType($array['response_type'] ?? 'code');
        $instance->setGrantType($array['grant_type'] ?? 'authorization_code');
        $instance->setAdditionalParams($array['additional_params'] ?? []);

        return $instance;
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'client_url' => $this->clientUrl,
            'scope' => $this->scope,
            'authorization_url' => $this->authorizationUrl,
            'authorization_content_type' => $this->authorizationContentType,
            'issuer_url' => $this->issuerUrl,
            'redirect_uri' => $this->redirectUri,
            'use_pkce' => $this->usePKCE,
            'response_type' => $this->responseType,
            'grant_type' => $this->grantType,
            'additional_params' => $this->additionalParams,
        ];
    }
}
