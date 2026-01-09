<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use function Hyperf\Support\env;

$organizationWhitelists = parse_json_config(env('ORGANIZATION_WHITELISTS'));
$superWhitelists = parse_json_config(env('SUPER_WHITELISTS', '[]'));
return [
    // exceedsleveladministrator
    'super_whitelists' => $superWhitelists,
    // byattemporaryo clocknothavepermissionsystem,env configurationorganizationadministrator
    'organization_whitelists' => $organizationWhitelists,
];
