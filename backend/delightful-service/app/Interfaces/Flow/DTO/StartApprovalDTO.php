<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\DTO;

class StartApprovalDTO extends AbstractFlowDTO
{
    // approvaltemplate编码
    public string $templateCode = '';

    // approvaltable单data
    public array $formData = [];

    // approvalstreamdata
    public array $approvalData = [];

    // departmentID
    public ?string $departmentId = null;

    public function getTemplateCode(): string
    {
        return $this->templateCode;
    }

    public function setTemplateCode(?string $templateCode): StartApprovalDTO
    {
        $this->templateCode = $templateCode ?? '';
        return $this;
    }

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function setFormData(?array $formData): StartApprovalDTO
    {
        $this->formData = $formData ?? [];
        return $this;
    }

    public function getApprovalData(): array
    {
        return $this->approvalData;
    }

    public function setApprovalData(?array $approvalData): StartApprovalDTO
    {
        $this->approvalData = $approvalData ?? [];
        return $this;
    }

    public function getDepartmentId(): ?string
    {
        return $this->departmentId;
    }

    public function setDepartmentId(?string $departmentId): StartApprovalDTO
    {
        $this->departmentId = $departmentId;
        return $this;
    }

    public function toBody(): array
    {
        return [
            'template_code' => $this->templateCode,
            'form_Data' => $this->formData,
            'approval_Data' => $this->approvalData,
            'department_id' => $this->departmentId,
        ];
    }
}
