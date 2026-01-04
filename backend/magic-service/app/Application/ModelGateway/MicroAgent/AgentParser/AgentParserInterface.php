<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Application\ModelGateway\MicroAgent\AgentParser;

interface AgentParserInterface
{
    /**
     * Load and parse agent file.
     */
    public function loadFromFile(string $filePath): array;

    /**
     * Get supported file extensions.
     */
    public function getSupportedExtensions(): array;

    /**
     * Check if the parser supports the given file extension.
     */
    public function supports(string $extension): bool;
}
