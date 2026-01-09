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
            '*.required' => 'Agent全局setting不能为空',
            '*.array' => 'Agent全局setting必须是数组',
            '*.type.required' => 'type不能为空',
            '*.type.integer' => 'type必须为整数',
            '*.type.in' => 'type值无效',
            '*.status.required' => 'status不能为空',
            '*.status.integer' => 'status必须为整数',
            '*.status.in' => 'status值无效',
            '*.extra.array' => '额外参数必须是数组',
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
