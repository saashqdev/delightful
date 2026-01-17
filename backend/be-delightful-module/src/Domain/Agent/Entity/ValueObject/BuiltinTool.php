<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

use function Hyperf\Translation\trans;

enum BuiltinTool: string
{
    // File operations (FileOperations)
    case ListDir = 'list_dir';
    case ReadFiles = 'read_files';
    case WriteFile = 'write_file';
    case EditFile = 'edit_file';
    case MultiEditFile = 'multi_edit_file';
    case DeleteFile = 'delete_files';
    case FileSearch = 'file_search';
    case GrepSearch = 'grep_search';

    // Search extraction (SearchExtraction)
    case WebSearch = 'web_search';
    case ImageSearch = 'image_search';
    case ReadWebpagesAsMarkdown = 'read_webpages_as_markdown';
    case DownloadFromUrls = 'download_from_urls';
    case DownloadFromMarkdown = 'download_from_markdown';

    // Content processing (ContentProcessing)
    case VisualUnderstanding = 'visual_understanding';
    case GenerateImage = 'generate_image';
    case ConvertToMarkdown = 'convert_to_markdown';

    // System execution (SystemExecution)
    case ShellExec = 'shell_exec';
    case RunPythonSnippet = 'run_python_snippet';

    /**
     * Get tool user-friendly name.
     */
    public function getToolName(): string
    {
        return trans("builtin_tools.names.{$this->value}");
    }

    /**
     * Get tool user-friendly description.
     */
    public function getToolDescription(): string
    {
        return trans("builtin_tools.descriptions.{$this->value}");
    }

    /**
     * Get tool icon.
     */
    public function getToolIcon(): string
    {
        // Temporarily return empty string, waiting for frontend to provide icon content
        return '';
    }

    /**
     * Get tool category.
     */
    public function getToolCategory(): BuiltinToolCategory
    {
        return match ($this) {
            // File operations
            self::ListDir, self::ReadFiles, self::WriteFile, self::EditFile, self::MultiEditFile,
            self::DeleteFile, self::FileSearch, self::GrepSearch => BuiltinToolCategory::FileOperations,

            // Search extraction
            self::WebSearch, self::ImageSearch, self::ReadWebpagesAsMarkdown,
            self::DownloadFromUrls, self::DownloadFromMarkdown => BuiltinToolCategory::SearchExtraction,

            // Content processing
            self::VisualUnderstanding, self::GenerateImage, self::ConvertToMarkdown => BuiltinToolCategory::ContentProcessing,

            // System execution
            self::ShellExec, self::RunPythonSnippet => BuiltinToolCategory::SystemExecution,
        };
    }

    /**
     * Get tool type.
     */
    public static function getToolType(): BeDelightfulAgentToolType
    {
        return BeDelightfulAgentToolType::BuiltIn;
    }

    /**
     * Get all required tools.
     * @return array<BuiltinTool>
     */
    public static function getRequiredTools(): array
    {
        return [
            // Basic file CRUD + directory view
            self::ListDir,
            self::ReadFiles,
            self::WriteFile,
            self::EditFile,
            self::MultiEditFile,
            self::DeleteFile,
            self::FileSearch,
            self::GrepSearch,
            // Internet search and reading
            self::WebSearch,
            self::ImageSearch,
            self::ReadWebpagesAsMarkdown,
            // Visual understanding
            self::VisualUnderstanding,
            // Command line execution
            self::ShellExec,
        ];
    }

    public static function isValidTool(string $tool): bool
    {
        return (bool) self::tryFrom($tool);
    }

    /**
     * Determine whether tool is required.
     */
    public function isRequired(): bool
    {
        return in_array($this, self::getRequiredTools(), true);
    }
}
