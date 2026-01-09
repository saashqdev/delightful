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
            'document_file.required' => 'documentfilenotcanforempty',
            'document_file.name.required' => 'documentnamenotcanforempty',
            'document_file.name.max' => 'documentnamelengthnotcan超pass255character',
            'document_file.key.required' => 'documentkeynotcanforempty',
            'document_file.key.max' => 'documentkeylengthnotcan超pass255character',
            'fragment_config.required' => 'slicesegmentconfigurationnotcanforempty',
            'fragment_config.mode.required' => 'minutesegment模typenotcanforempty',
            'fragment_config.mode.integer' => 'minutesegment模typemustisinteger',
            'fragment_config.mode.in' => 'minutesegment模typemustis 1(通use模type) or 2(parent-childminutesegment)',
            'fragment_config.normal.required_if' => '通use模typeconfigurationnotcanforempty',
            'fragment_config.normal.text_preprocess_rule.required_if' => 'text预processrulenotcanforempty',
            'fragment_config.normal.text_preprocess_rule.*.required' => '预processruleitemnotcanforempty',
            'fragment_config.normal.text_preprocess_rule.*.integer' => '预processrulemustisinteger',
            'fragment_config.normal.text_preprocess_rule.*.in' => '预processrulemustis 1(replacespaceetc) or 2(deleteURLandmailbox)',
            'fragment_config.normal.segment_rule.required_if' => 'minutesegmentrulenotcanforempty',
            'fragment_config.normal.segment_rule.separator.required_if' => 'minutesegmentidentifiernotcanforempty',
            'fragment_config.normal.segment_rule.chunk_size.required_if' => 'mostbigminutesegmentlengthnotcanforempty',
            'fragment_config.normal.segment_rule.chunk_size.min' => 'mostbigminutesegmentlengthmustgreater than0',
            'fragment_config.normal.segment_rule.chunk_overlap.required_if' => 'minutesegmentoverlaplengthnotcanforempty',
            'fragment_config.normal.segment_rule.chunk_overlap.min' => 'minutesegmentoverlaplengthmustgreater thanequal0',
            'fragment_config.parent_child.required_if' => 'parent-childminutesegmentconfigurationnotcanforempty',
            'fragment_config.parent_child.separator.required_if' => 'minutesegmentidentifiernotcanforempty',
            'fragment_config.parent_child.chunk_size.required_if' => 'textnotcanforempty',
            'fragment_config.parent_child.parent_mode.required_if' => '父piece模typenotcanforempty',
            'fragment_config.parent_child.parent_mode.in' => '父piece模typemustis 1(segment落) or 2(authority)',
            'fragment_config.parent_child.child_segment_rule.required_if' => '子pieceminutesegmentrulenotcanforempty',
            'fragment_config.parent_child.child_segment_rule.separator.required_if' => '子pieceminutesegmentidentifiernotcanforempty',
            'fragment_config.parent_child.child_segment_rule.chunk_size.required_if' => '子piecemostbigminutesegmentlengthnotcanforempty',
            'fragment_config.parent_child.child_segment_rule.chunk_size.min' => '子piecemostbigminutesegmentlengthmustgreater than0',
            'fragment_config.parent_child.parent_segment_rule.required_if' => '父pieceminutesegmentrulenotcanforempty',
            'fragment_config.parent_child.parent_segment_rule.separator.required_if' => '父pieceminutesegmentidentifiernotcanforempty',
            'fragment_config.parent_child.parent_segment_rule.chunk_size.required_if' => '父piecemostbigminutesegmentlengthnotcanforempty',
            'fragment_config.parent_child.parent_segment_rule.chunk_size.min' => '父piecemostbigminutesegmentlengthmustgreater than0',
            'fragment_config.parent_child.text_preprocess_rule.required_if' => 'text预processrulenotcanforempty',
            'fragment_config.parent_child.text_preprocess_rule.*.required' => '预processruleitemnotcanforempty',
            'fragment_config.parent_child.text_preprocess_rule.*.integer' => '预processrulemustisinteger',
            'fragment_config.parent_child.text_preprocess_rule.*.in' => '预processrulemustis 1(replacespaceetc) or 2(deleteURLandmailbox)',
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
