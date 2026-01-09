<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    // 默认语言
    'locale' => 'zh_CN',
    // 回退语言，when默认语言的语言文本没有提供时，就会use回退语言的对应语言文本
    'fallback_locale' => 'en_US',
    // 语言file存放的file夹
    'path' => BASE_PATH . '/storage/languages',
];
