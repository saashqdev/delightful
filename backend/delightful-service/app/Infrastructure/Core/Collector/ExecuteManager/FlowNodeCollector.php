<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Collector\ExecuteManager;

use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use Hyperf\Di\Annotation\AnnotationCollector;
use RuntimeException;

class FlowNodeCollector
{
    /**
     * @var array<int, array<string, FlowNodeDefine>>
     */
    protected static ?array $defines = null;

    /**
     * @var array<int, FlowNodeDefine>
     */
    protected static array $latestDefines = [];

    public static function get(int $type, string $version = 'latest'): FlowNodeDefine
    {
        $list = self::list();
        if ($version === 'latest' || $version === '') {
            $nodeDefine = self::$latestDefines[$type] ?? null;
        } else {
            $nodeDefine = $list[$type][$version] ?? null;
        }
        if (! $nodeDefine) {
            throw new RuntimeException(sprintf('FlowNodeDefine not found, type: %d, version: %s', $type, $version));
        }
        return $nodeDefine;
    }

    /**
     * @return array<int, array<string, FlowNodeDefine>>
     */
    public static function list(): array
    {
        if (! is_null(self::$defines)) {
            return self::$defines;
        }
        $defines = AnnotationCollector::getClassesByAnnotation(FlowNodeDefine::class);
        $list = [];
        /**
         * @var string $runnerClass
         * @var FlowNodeDefine $define
         */
        foreach ($defines as $runnerClass => $define) {
            if (! class_exists($runnerClass) || ! $define->isEnabled()) {
                continue;
            }
            $define->setRunner($runnerClass);
            $list[$define->getType()][$define->getVersion()] = $define;

            $lastDefine = self::$latestDefines[$define->getType()] ?? null;
            if (! $lastDefine) {
                self::$latestDefines[$define->getType()] = $define;
            } elseif (version_compare($lastDefine->getVersion(), $define->getVersion(), '<')) {
                self::$latestDefines[$define->getType()] = $define;
            }
        }
        // type isinteger,according tofromsmalltobigrowcolumnonetime.twolevelisversion v0,v1,alsoneedfromsmalltobigrowcolumn
        ksort($list);
        foreach ($list as $type => $versions) {
            ksort($list[$type]);
        }
        self::$defines = $list;
        return self::$defines;
    }
}
