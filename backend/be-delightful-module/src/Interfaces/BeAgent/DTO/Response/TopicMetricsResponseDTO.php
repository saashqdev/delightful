<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

class TopicMetricsResponseDTO extends AbstractDTO 
{
 /** * @var StatusMetricsDTO StatusData */ 
    protected StatusMetricsDTO $statusMetrics; /** * @var TotalMetricsDTO Data */ 
    protected TotalMetricsDTO $totalMetrics; /** * GetStatus. */ 
    public function getStatusMetrics(): StatusMetricsDTO 
{
 return $this->statusMetrics; 
}
 /** * Set Status. */ 
    public function setStatusMetrics(StatusMetricsDTO $statusMetrics): self 
{
 $this->statusMetrics = $statusMetrics; return $this; 
}
 /** * Get. */ 
    public function getTotalMetrics(): TotalMetricsDTO 
{
 return $this->totalMetrics; 
}
 /** * Set . */ 
    public function setTotalMetrics(TotalMetricsDTO $totalMetrics): self 
{
 $this->totalMetrics = $totalMetrics; return $this; 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'status_metrics' => $this->statusMetrics->toArray(), 'total_metrics' => $this->totalMetrics->toArray(), ]; 
}
 
}
 
