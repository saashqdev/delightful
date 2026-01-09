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
            'document_file.required' => 'documentfilenot能forempty',
            'document_file.name.required' => 'documentnamenot能forempty',
            'document_file.name.max' => 'documentnamelengthnot能超pass255character',
            'document_file.key.required' => 'document键not能forempty',
            'document_file.key.max' => 'document键lengthnot能超pass255character',
            'fragment_config.required' => 'slicesegmentconfigurationnot能forempty',
            'fragment_config.mode.required' => 'minutesegment模typenot能forempty',
            'fragment_config.mode.integer' => 'minutesegment模typemustisinteger',
            'fragment_config.mode.in' => 'minutesegment模typemustis 1(通use模type) or 2(父子minutesegment)',
            'fragment_config.normal.required_if' => '通use模typeconfigurationnot能forempty',
            'fragment_config.normal.text_preprocess_rule.required_if' => 'text预processrulenot能forempty',
            'fragment_config.normal.text_preprocess_rule.*.required' => '预processruleitemnot能forempty',
            'fragment_config.normal.text_preprocess_rule.*.integer' => '预processrulemustisinteger',
            'fragment_config.normal.text_preprocess_rule.*.in' => '预processrulemustis 1(替换spaceetc) or 2(deleteURLand邮箱)',
            'fragment_config.normal.segment_rule.required_if' => 'minutesegmentrulenot能forempty',
            'fragment_config.normal.segment_rule.separator.required_if' => 'minutesegmentidentifiernot能forempty',
            'fragment_config.normal.segment_rule.chunk_size.required_if' => 'most大minutesegmentlengthnot能forempty',
            'fragment_config.normal.segment_rule.chunk_size.min' => 'most大minutesegmentlengthmustgreater than0',
            'fragment_config.normal.segment_rule.chunk_overlap.required_if' => 'minutesegment重叠lengthnot能forempty',
            'fragment_config.normal.segment_rule.chunk_overlap.min' => 'minutesegment重叠lengthmustgreater thanequal0',
            'fragment_config.parent_child.required_if' => '父子minutesegmentconfigurationnot能forempty',
            'fragment_config.parent_child.separator.required_if' => 'minutesegmentidentifiernot能forempty',
            'fragment_config.parent_child.chunk_size.required_if' => 'textnot能forempty',
            'fragment_config.parent_child.parent_mode.required_if' => '父piece模typenot能forempty',
            'fragment_config.parent_child.parent_mode.in' => '父piece模typemustis 1(segment落) or 2(权威)',
            'fragment_config.parent_child.child_segment_rule.required_if' => '子pieceminutesegmentrulenot能forempty',
            'fragment_config.parent_child.child_segment_rule.separator.required_if' => '子pieceminutesegmentidentifiernot能forempty',
            'fragment_config.parent_child.child_segment_rule.chunk_size.required_if' => '子piecemost大minutesegmentlengthnot能forempty',
            'fragment_config.parent_child.child_segment_rule.chunk_size.min' => '子piecemost大minutesegmentlengthmustgreater than0',
            'fragment_config.parent_child.parent_segment_rule.required_if' => '父pieceminutesegmentrulenot能forempty',
            'fragment_config.parent_child.parent_segment_rule.separator.required_if' => '父pieceminutesegmentidentifiernot能forempty',
            'fragment_config.parent_child.parent_segment_rule.chunk_size.required_if' => '父piecemost大minutesegmentlengthnot能forempty',
            'fragment_config.parent_child.parent_segment_rule.chunk_size.min' => '父piecemost大minutesegmentlengthmustgreater than0',
            'fragment_config.parent_child.text_preprocess_rule.required_if' => 'text预processrulenot能forempty',
            'fragment_config.parent_child.text_preprocess_rule.*.required' => '预processruleitemnot能forempty',
            'fragment_config.parent_child.text_preprocess_rule.*.integer' => '预processrulemustisinteger',
            'fragment_config.parent_child.text_preprocess_rule.*.in' => '预processrulemustis 1(替换spaceetc) or 2(deleteURLand邮箱)',
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
