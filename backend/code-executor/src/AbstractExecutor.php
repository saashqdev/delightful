<?php

declare(strict_types=1);
/**
 * This file is part of Dtyq.
 */

namespace Delightful\CodeExecutor;

use Dtyq\CodeExecutor\Contract\ExecutorInterface;
use Dtyq\CodeExecutor\Exception\ExecuteException;
use Dtyq\CodeExecutor\Exception\ExecuteFailedException;
use Dtyq\CodeExecutor\Exception\InvalidArgumentException;

use function Dtyq\CodeExecutor\Utils\stripPHPTags;

abstract class AbstractExecutor implements ExecutorInterface
{
    /**
     * @throws InvalidArgumentException
     * @throws ExecuteFailedException
     */
    public function execute(ExecutionRequest $request): ExecutionResult
    {
        $language = $request->getLanguage();

        if (! $this->isLanguageSupported($language)) {
            throw new InvalidArgumentException("Language {$language->value} is not supported by this executor");
        }

        $request->setCode(stripPHPTags($request->getCode()));

        try {
            return $this->doExecute($request);
        } catch (ExecuteException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new ExecuteFailedException("Failed to execute code: {$e->getMessage()}", previous: $e);
        }
    }

    abstract protected function doExecute(ExecutionRequest $request): ExecutionResult;

    /**
     * Check if language is supported
     *
     * @param Language $language The language to check
     * @return bool Whether supported
     */
    protected function isLanguageSupported(Language $language): bool
    {
        return in_array($language, $this->getSupportedLanguages(), true);
    }
}
