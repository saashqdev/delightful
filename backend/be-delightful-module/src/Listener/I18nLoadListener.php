<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\list ener;

use Hyperf\Contract\TranslatorInterface;
use Hyperf\Event\Contract\list enerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\Translation\Translator;

class I18nLoadlist ener implements list enerInterface 
{
 
    protected TranslatorInterface $translator; 
    public function __construct(TranslatorInterface $translator) 
{
 $this->translator = $translator; 
}
 
    public function listen(): array 
{
 return [ BootApplication::class, ]; 
}
 
    public function process(object $event): void 
{
 // super-magic-module i18n file path $i18nPath = BASE_PATH . '/vendor/dtyq/super-magic-module/storage/languages'; $this->loadTranslations($i18nPath); 
}
 
    private function loadTranslations(string $path): void 
{
 if (! is_dir($path)) 
{
 return; 
}
 $languages = scandir($path); foreach ($languages as $language) 
{
 if ($language === '.' || $language === '..') 
{
 continue; 
}
 $langPath = $path . '/' . $language; if (is_dir($langPath)) 
{
 $files = scandir($langPath); foreach ($files as $file) 
{
 if (pathinfo($file, PATHINFO_EXTENSION) === 'php') 
{
 $group = pathinfo($file, PATHINFO_FILENAME); $filePath = $langPath . '/' . $file; $translations = require $filePath; // Manually add translation lines if needed if ($translations && is_array($translations)) 
{
 $lines = []; foreach ($translations as $key => $value) 
{
 $lines[ 
{
$group
}
.
{
$key
}
 ] = $value; 
}
 /** @var Translator $translator */ $translator = $this->translator; $translator->addLines($lines, $language); 
}
 
}
 
}
 
}
 
}
 
}
 
}
 
