<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\KnowledgeBase\DTO\Request;

use App\Domain\KnowledgeBase\Entity\ValueObject\FragmentConfig;
use App\Infrastructure\Core\AbstractRequestDTO;
use App\Interfaces\KnowledgeBase\DTO\DocumentFile\AbstractDocumentFileDTO;
use App\Interfaces\KnowledgeBase\DTO\DocumentFile\DocumentFileDTOInterface;

class FragmentPreviewRequestDTO extends AbstractRequestDTO
{
    public DocumentFileDTOInterface $documentFile;

    public FragmentConfig $fragmentConfig;

    public static function getHyperfValidationRules(): array
    {
        return [
            'document_file' => 'required|array',
            'document_file.type' => 'integer|between:1,2',
            'document_file.name' => 'required|string',
            'document_file.key' => 'required_if:document_file.type,1|string',
            'document_file.third_file_id' => 'required_if:document_file.type,2|string',
            'document_file.platform_type' => 'required_if:document_file.type,2|string',
            'fragment_config' => 'required|array',
            'fragment_config.mode' => 'required|integer|in:1,2',
            'fragment_config.normal' => 'required_if:fragment_config.mode,1|array',
            'fragment_config.normal.text_preprocess_rule' => 'array',
            'fragment_config.normal.text_preprocess_rule.*' => 'required|integer|in:1,2',
            'fragment_config.normal.segment_rule' => 'required_if:fragment_config.mode,1|array',
            'fragment_config.normal.segment_rule.separator' => 'required_if:fragment_config.mode,1|string',
            'fragment_config.normal.segment_rule.chunk_size' => 'required_if:fragment_config.mode,1|integer|min:1',
            'fragment_config.normal.segment_rule.chunk_overlap' => 'required_if:fragment_config.mode,1|integer|min:0',
            'fragment_config.parent_child' => 'required_if:fragment_config.mode,2|array',
            'fragment_config.parent_child.separator' => 'required_if:fragment_config.mode,2|string',
            'fragment_config.parent_child.chunk_size' => 'required_if:fragment_config.mode,2|string',
            'fragment_config.parent_child.parent_mode' => 'required_if:fragment_config.mode,2|integer|in:1,2',
            'fragment_config.parent_child.child_segment_rule' => 'required_if:fragment_config.mode,2|array',
            'fragment_config.parent_child.child_segment_rule.separator' => 'required_if:fragment_config.mode,2|string',
            'fragment_config.parent_child.child_segment_rule.chunk_size' => 'required_if:fragment_config.mode,2|integer|min:1',
            'fragment_config.parent_child.parent_segment_rule' => 'required_if:fragment_config.mode,2|array',
            'fragment_config.parent_child.parent_segment_rule.separator' => 'required_if:fragment_config.mode,2|string',
            'fragment_config.parent_child.parent_segment_rule.chunk_size' => 'required_if:fragment_config.mode,2|integer|min:1',
            'fragment_config.parent_child.text_preprocess_rule' => 'array',
            'fragment_config.parent_child.text_preprocess_rule.*' => 'required|integer|in:1,2',
        ];
    }

    public static function getHyperfValidationMessage(): array
    {
        return [
            'document_file.required' => 'documentfilenot能为空',
            'document_file.name.required' => 'documentnamenot能为空',
            'document_file.name.max' => 'documentnamelengthnot能超过255个字符',
            'document_file.key.required' => 'document键not能为空',
            'document_file.key.max' => 'document键lengthnot能超过255个字符',
            'fragment_config.required' => '片段configurationnot能为空',
            'fragment_config.mode.required' => '分段模式not能为空',
            'fragment_config.mode.integer' => '分段模式must是整数',
            'fragment_config.mode.in' => '分段模式must是 1(通use模式) or 2(父子分段)',
            'fragment_config.normal.required_if' => '通use模式configurationnot能为空',
            'fragment_config.normal.text_preprocess_rule.required_if' => '文本预processrulenot能为空',
            'fragment_config.normal.text_preprocess_rule.*.required' => '预processrule项not能为空',
            'fragment_config.normal.text_preprocess_rule.*.integer' => '预processrulemust是整数',
            'fragment_config.normal.text_preprocess_rule.*.in' => '预processrulemust是 1(替换空格etc) or 2(deleteURL和邮箱)',
            'fragment_config.normal.segment_rule.required_if' => '分段rulenot能为空',
            'fragment_config.normal.segment_rule.separator.required_if' => '分段标识符not能为空',
            'fragment_config.normal.segment_rule.chunk_size.required_if' => 'most大分段lengthnot能为空',
            'fragment_config.normal.segment_rule.chunk_size.min' => 'most大分段lengthmustgreater than0',
            'fragment_config.normal.segment_rule.chunk_overlap.required_if' => '分段重叠lengthnot能为空',
            'fragment_config.normal.segment_rule.chunk_overlap.min' => '分段重叠lengthmustgreater thanequal0',
            'fragment_config.parent_child.required_if' => '父子分段configurationnot能为空',
            'fragment_config.parent_child.separator.required_if' => '分段标识符not能为空',
            'fragment_config.parent_child.chunk_size.required_if' => '文本not能为空',
            'fragment_config.parent_child.parent_mode.required_if' => '父块模式not能为空',
            'fragment_config.parent_child.parent_mode.in' => '父块模式must是 1(段落) or 2(权威)',
            'fragment_config.parent_child.child_segment_rule.required_if' => '子块分段rulenot能为空',
            'fragment_config.parent_child.child_segment_rule.separator.required_if' => '子块分段标识符not能为空',
            'fragment_config.parent_child.child_segment_rule.chunk_size.required_if' => '子块most大分段lengthnot能为空',
            'fragment_config.parent_child.child_segment_rule.chunk_size.min' => '子块most大分段lengthmustgreater than0',
            'fragment_config.parent_child.parent_segment_rule.required_if' => '父块分段rulenot能为空',
            'fragment_config.parent_child.parent_segment_rule.separator.required_if' => '父块分段标识符not能为空',
            'fragment_config.parent_child.parent_segment_rule.chunk_size.required_if' => '父块most大分段lengthnot能为空',
            'fragment_config.parent_child.parent_segment_rule.chunk_size.min' => '父块most大分段lengthmustgreater than0',
            'fragment_config.parent_child.text_preprocess_rule.required_if' => '文本预processrulenot能为空',
            'fragment_config.parent_child.text_preprocess_rule.*.required' => '预processrule项not能为空',
            'fragment_config.parent_child.text_preprocess_rule.*.integer' => '预processrulemust是整数',
            'fragment_config.parent_child.text_preprocess_rule.*.in' => '预processrulemust是 1(替换空格etc) or 2(deleteURL和邮箱)',
        ];
    }

    public function getDocumentFile(): DocumentFileDTOInterface
    {
        return $this->documentFile;
    }

    public function setDocumentFile(array|DocumentFileDTOInterface $documentFile): void
    {
        is_array($documentFile) && $documentFile = AbstractDocumentFileDTO::fromArray($documentFile);
        $this->documentFile = $documentFile;
    }

    public function getFragmentConfig(): FragmentConfig
    {
        return $this->fragmentConfig;
    }

    public function setFragmentConfig(array $fragmentConfig): void
    {
        $this->fragmentConfig = FragmentConfig::fromArray($fragmentConfig);
    }
}
