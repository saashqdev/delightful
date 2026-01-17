<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Exception;
use Hyperf\HttpServer\Contract\RequestInterface;

class GetTopicMessagesByTopicIdRequestDTO
{
    /**
     * Valid sort directions.
     */
    private const VALID_SORT_DIRECTIONS = ['asc', 'desc'];

    /**
     * @var int Topic ID
     */
    protected int $topicId = 0;

    /**
     * @var int Page number
     */
    protected int $page = 1;

    /**
     * @var int Page size
     */
    protected int $pageSize = 20;

    /**
     * @var string Sort direction
     */
    protected string $sortDirection = 'asc';

    /**
     * Construct from request array.
     */
    public function __construct(array $data = [])
    {
        $this->topicId = (int) ($data['topic_id'] ?? 0);
        $this->page = (int) ($data['page'] ?? 1);
        $this->pageSize = (int) ($data['page_size'] ?? 20);
        $this->setSortDirection($data['sort_direction'] ?? 'asc');
    }

    /**
     * Create instance from HTTP request object.
     */
    public static function fromRequest(RequestInterface $request): self
    {
        $id = $request->route('id'); // Get topic_id from route parameter

        return new self([
            'topic_id' => $id,
            'page' => $request->input('page', 1),
            'page_size' => $request->input('page_size', 20),
            'sort_direction' => $request->input('sort_direction', 'asc'),
        ]);
    }

    /**
     * Get topic ID.
     */
    public function getTopicId(): int
    {
        return $this->topicId;
    }

    /**
     * Get page number.
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Get page size.
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Get sort direction.
     */
    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    /**
     * Set sort direction.
     *
     * @param string $sortDirection Sort direction
     * @throws Exception Throws exception if sort direction is invalid
     */
    protected function setSortDirection(string $sortDirection): void
    {
        $sortDirection = strtolower(trim($sortDirection));

        if (! in_array($sortDirection, self::VALID_SORT_DIRECTIONS, true)) {
            ExceptionBuilder::throw(
                GenericErrorCode::ParameterMissing,
                'sort_direction.invalid_value'
            );
        }

        $this->sortDirection = $sortDirection;
    }
}
