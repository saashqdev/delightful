<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Exception;
use Hyperf\HttpServer\Contract\RequestInterface;

class GetTopicMessagesByTopicIdRequestDTO 
{
 /** * ValidSort. */ 
    private 
    const VALID_SORT_DIRECTIONS = ['asc', 'desc']; /** * @var int topic ID */ 
    protected int $topicId = 0; /** * @var int Page number */ 
    protected int $page = 1; /** * @var int Per pageSize */ 
    protected int $pageSize = 20; /** * @var string Sort */ 
    protected string $sortDirection = 'asc'; /** * FromRequestArray. */ 
    public function __construct(array $data = []) 
{
 $this->topicId = (int) ($data['topic_id'] ?? 0); $this->page = (int) ($data['page'] ?? 1); $this->pageSize = (int) ($data['page_size'] ?? 20); $this->setSortDirection($data['sort_direction'] ?? 'asc'); 
}
 /** * From HTTP RequestObjectCreateInstance. */ 
    public 
    static function fromRequest(RequestInterface $request): self 
{
 $id = $request->route('id'); // Get topic_id from route parameter return new self([ 'topic_id' => $id, 'page' => $request->input('page', 1), 'page_size' => $request->input('page_size', 20), 'sort_direction' => $request->input('sort_direction', 'asc'), ]); 
}
 /** * Gettopic ID. */ 
    public function getTopicId(): int 
{
 return $this->topicId; 
}
 /** * GetPage number. */ 
    public function getPage(): int 
{
 return $this->page; 
}
 /** * GetPer pageSize. */ 
    public function getPageSize(): int 
{
 return $this->pageSize; 
}
 /** * GetSort. */ 
    public function getSortDirection(): string 
{
 return $this->sortDirection; 
}
 /** * Set Sort. * * @param string $sortDirection Sort * @throws Exception IfSortInvalidThrowException */ 
    protected function setSortDirection(string $sortDirection): void 
{
 $sortDirection = strtolower(trim($sortDirection)); if (! in_array($sortDirection, self::VALID_SORT_DIRECTIONS, true)) 
{
 ExceptionBuilder::throw( GenericErrorCode::ParameterMissing, 'sort_direction.invalid_value' ); 
}
 $this->sortDirection = $sortDirection; 
}
 
}
 
