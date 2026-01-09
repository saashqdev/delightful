<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    // defaultlanguage
    'locale' => 'zh_CN',
    // backlanguage，whendefaultlanguage的languagetextnothave提供o clock，thenwillusebacklanguage的对应languagetext
    'fallback_locale' => 'en_US',
    // languagefile存放的file夹
    'path' => BASE_PATH . '/storage/languages',
];
