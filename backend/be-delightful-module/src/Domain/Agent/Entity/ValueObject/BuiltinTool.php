<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

use function Hyperf\Translation\trans;

enum Builtintool : string 
{
 // File (FileOperations) case list Dir = 'list_dir'; case ReadFiles = 'read_files'; case WriteFile = 'write_file'; case EditFile = 'edit_file'; case MultiEditFile = 'multi_edit_file'; case delete File = 'delete_files'; case FileSearch = 'file_search'; case GrepSearch = 'grep_search'; // Search (SearchExtraction) case WebSearch = 'web_search'; case ImageSearch = 'image_search'; case ReadWebpagesAsmark down = 'read_webpages_as_markdown'; case DownloadFromUrls = 'download_from_urls'; case DownloadFrommark down = 'download_from_markdown'; // Contentprocess (Contentprocess ing) case VisualUnderstanding = 'visual_understanding'; case Generate Image = 'generate_image'; case ConvertTomark down = 'convert_to_markdown'; // Systemexecute (SystemExecution) case ShellExec = 'shell_exec'; case RunPythonSnippet = 'run_python_snippet'; /** * Gettool user Name. */ 
    public function gettool Name(): string 
{
 return trans( builtin_tools.names.
{
$this->value
}
 ); 
}
 /** * Gettool user Description. */ 
    public function gettool Description(): string 
{
 return trans( builtin_tools.descriptions.
{
$this->value
}
 ); 
}
 /** * Gettool Icon. */ 
    public function gettool Icon(): string 
{
 // TemporarilyReturn EmptyStringWaitingFrontendIconContent return ''; 
}
 /** * Gettool Category. */ 
    public function gettool Category(): Builtintool Category 
{
 return match ($this) 
{
 // File self::list Dir, self::ReadFiles, self::WriteFile, self::EditFile, self::MultiEditFile, self::delete File, self::FileSearch, self::GrepSearch => Builtintool Category::FileOperations, // Search self::WebSearch, self::ImageSearch, self::ReadWebpagesAsmark down, self::DownloadFromUrls, self::DownloadFrommark down => Builtintool Category::SearchExtraction, // Contentprocess self::VisualUnderstanding, self::Generate Image, self::ConvertTomark down => Builtintool Category::Contentprocess ing, // Systemexecute self::ShellExec, self::RunPythonSnippet => Builtintool Category::SystemExecution, 
}
; 
}
 /** * Gettool Type. */ 
    public 
    static function gettool Type(): BeDelightfulAgenttool Type 
{
 return BeDelightfulAgenttool Type::BuiltIn; 
}
 /** * GetAlltool . * @return array<Builtintool > */ 
    public 
    static function getRequiredtool s(): array 
{
 return [ // BaseFile + DirectoryView self::list Dir, self::ReadFiles, self::WriteFile, self::EditFile, self::MultiEditFile, self::delete File, self::FileSearch, self::GrepSearch, // Search self::WebSearch, self::ImageSearch, self::ReadWebpagesAsmark down, // self::VisualUnderstanding, // Rowexecute self::ShellExec, ]; 
}
 
    public 
    static function isValidtool (string $tool): bool 
{
 return (bool) self::tryFrom($tool); 
}
 /** * Determinetool whether as tool . */ 
    public function isRequired(): bool 
{
 return in_array($this, self::getRequiredtool s(), true); 
}
 
}
 
