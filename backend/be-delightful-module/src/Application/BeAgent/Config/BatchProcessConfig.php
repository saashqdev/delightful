<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Config;

/** * Batch process Configuration. * Batchprocess ConfigurationClass - BatchFileSaveItemParameter. */

class Batchprocess Config 
{
 /** * ConcurrencyConfiguration. */ 
    public 
    const int DEFAULT_MAX_CONCURRENCY = 5; // DefaultMaximumConcurrencyFileIOmemory consume  
    public 
    const int MIN_CONCURRENCY = 1; // MinimumConcurrency 
    public 
    const int MAX_CONCURRENCY = 8; // MaximumConcurrencyUpper limitFileprocess Should not be too high  /** * BatchSizeLimit. */ 
    public 
    const int DEFAULT_BATCH_SIZE_LIMIT = 50; // DefaultBatchSizeLimit 
    public 
    const int MAX_BATCH_SIZE_LIMIT = 100; // MaximumBatchSizeUpper limit /** * Switch. */ 
    public 
    const bool ENABLE_PERFORMANCE_MONITORING = true; // Switch 
    public 
    const bool ENABLE_DETAILED_LOGGING = true; // LogSwitch /** * GetMaximumConcurrency. * Fileprocess yes IOneed memory consume SystemResourceLimit. * * @param int $fileCount FileQuantity * @return int ActualConcurrency */ 
    public 
    static function getMaxConcurrency(int $fileCount): int 
{
 // Fileprocess ConcurrencyPolicy // 1. Considering each file maximum 10MB, too much concurrency will consume large amount of memory // 2. File upload involves temporary files and network IO, high resource consumption // 3. LockDatabaseJoinalso HaveLimit if ($fileCount == 1) 
{
 return 1; 
}
 if ($fileCount <= 3) 
{
 return 3; // File3Concurrency 
}
 // FileMaximumConcurrency5 return self::DEFAULT_MAX_CONCURRENCY; 
}
 /** * whether EnabledConcurrencyprocess . * * @param int $fileCount FileQuantity * @return bool whether EnabledConcurrency */ 
    public 
    static function shouldEnableConcurrency(int $fileCount): bool 
{
 // FileQuantityLess than2UsingConcurrency return $fileCount >= 2; 
}
 /** * GetBatchSizeLimit. * * @return int BatchSizeLimit */ 
    public 
    static function getBatchSizeLimit(): int 
{
 return self::DEFAULT_BATCH_SIZE_LIMIT; 
}
 /** * whether Enabled. * * @return bool whether Enabled */ 
    public 
    static function isPerformanceMonitoringEnabled(): bool 
{
 return self::ENABLE_PERFORMANCE_MONITORING; 
}
 /** * whether EnabledLog. * * @return bool whether EnabledLog */ 
    public 
    static function isDetailedLoggingEnabled(): bool 
{
 return self::ENABLE_DETAILED_LOGGING; 
}
 
}
 
