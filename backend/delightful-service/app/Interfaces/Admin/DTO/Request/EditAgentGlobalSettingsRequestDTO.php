<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Admin\DTO\Request;

use App\Domain\Admin\Entity\ValueObject\AdminGlobalSettingsStatus;
use App\Domain\Admin\Entity\ValueObject\AdminGlobalSettingsType;
use App\Infrastructure\Core\AbstractDTO;
use App\Interfaces\Admin\DTO\AgentGlobalSettingsDTO;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use InvalidArgumentException;

class EditAgentGlobalSettingsRequestDTO extends AbstractDTO
{
    /**
     * @var AgentGlobalSettingsDTO[]
     */
    private array $settings = [];

    public static function fromRequest(RequestInterface $request): self
    {
        $instance = new self();
        $data = ['settings' => $request->all()];

        $validator = di(ValidatorFactoryInterface::class)->make(
            $data,
            $instance->rules(),
            $instance->messages()
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        $instance->formatData($data);
        return $instance;
    }

    public function rules(): array
    {
        $typeValues = array_map(fn ($case) => (string) $case->value, AdminGlobalSettingsType::cases());
        $statusValues = array_map(fn ($case) => (string) $case->value, AdminGlobalSettingsStatus::cases());

        return [
            'settings.*' => ['required', 'array'],
            'settings.*.type' => [
                'required',
                'integer',
                'in:' . implode(',', $typeValues),
            ],
            'settings.*.status' => [
                'required',
                'integer',
                'in:' . implode(',', $statusValues),
            ],
            'settings.*.extra' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => 'Agentalllocalsettingcannotfornull',
            '*.array' => 'Agentalllocalsettingmustisarray',
            '*.type.required' => 'typecannotfornull',
            '*.type.integer' => 'typemustforinteger',
            '*.type.in' => 'typevalueinvalid',
            '*.status.required' => 'statuscannotfornull',
            '*.status.integer' => 'statusmustforinteger',
            '*.status.in' => 'statusvalueinvalid',
            '*.extra.array' => 'quotaoutsideparametermustisarray',
        ];
    }

    /**
     * @return AgentGlobalSettingsDTO[]
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    protected function formatData(array $data): void
    {
        foreach ($data['settings'] as $setting) {
            $this->settings[] = new AgentGlobalSettingsDTO([
                'type' => $setting['type'],
                'status' => $setting['status'],
                'extra' => $setting['extra'] ?? null,
            ]);
        }
    }
}
