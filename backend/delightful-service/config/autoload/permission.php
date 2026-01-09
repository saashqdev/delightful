<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use function Hyperf\Support\env;

$organizationWhitelists = parse_json_config(env('ORGANIZATION_WHITELISTS'));
$superWhitelists = parse_json_config(env('SUPER_WHITELISTS', '[]'));
return [
    // 超级管理员
    'super_whitelists' => $superWhitelists,
    // 由at暂时nothavepermissionsystem，env configurationorganization的管理员
    'organization_whitelists' => $organizationWhitelists,
];
