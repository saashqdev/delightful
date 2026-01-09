<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

use App\Infrastructure\Core\AbstractValueObject;

/**
 * voice转textresultvalueobject
 * support多language转录resultstorage.
 */
class VoiceTranscription extends AbstractValueObject
{
    /**
     * 多language转录result
     * format: ['zh_CN' => '转录text', 'en_US' => 'Transcription text', ...].
     * @var null|array<string, string>
     */
    private ?array $transcriptions;

    /**
     * errorinfo（if转录fail）.
     */
    private ?string $errorMessage;

    /**
     * 转录time戳.
     */
    private ?int $transcribedAt;

    /**
     * mainlanguagecode（default转录language）.
     */
    private ?string $primaryLanguage;

    /**
     * get所have转录result.
     * @return array<string, string>
     */
    public function getTranscriptions(): array
    {
        return $this->transcriptions ?? [];
    }

    /**
     * set转录result.
     * @param array<string, string> $transcriptions
     */
    public function setTranscriptions(array $transcriptions): self
    {
        $this->transcriptions = $transcriptions;
        return $this;
    }

    /**
     * add单language转录result.
     */
    public function addTranscription(string $language, string $text): self
    {
        if ($this->transcriptions === null) {
            $this->transcriptions = [];
        }
        $this->transcriptions[$language] = $text;
        return $this;
    }

    /**
     * getfinger定language转录result.
     */
    public function getTranscription(string $language): ?string
    {
        return $this->transcriptions !== null ? ($this->transcriptions[$language] ?? null) : null;
    }

    /**
     * getmainlanguage转录result.
     */
    public function getPrimaryTranscription(): ?string
    {
        if ($this->primaryLanguage !== null && $this->transcriptions !== null && isset($this->transcriptions[$this->primaryLanguage])) {
            return $this->transcriptions[$this->primaryLanguage];
        }

        // ifnothavesetmainlanguage，returnfirstcanuse转录result
        return ! empty($this->transcriptions) ? reset($this->transcriptions) : null;
    }

    /**
     * checkwhetherhavefinger定language转录result.
     */
    public function hasTranscription(string $language): bool
    {
        return $this->transcriptions !== null && isset($this->transcriptions[$language]) && ! empty($this->transcriptions[$language]);
    }

    /**
     * get所havesupportlanguagecode
     * @return string[]
     */
    public function getSupportedLanguages(): array
    {
        return array_keys($this->transcriptions ?? []);
    }

    /**
     * geterrorinfo.
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage ?? null;
    }

    /**
     * seterrorinfo.
     */
    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    /**
     * get转录time戳.
     */
    public function getTranscribedAt(): ?int
    {
        return $this->transcribedAt ?? null;
    }

    /**
     * set转录time戳.
     */
    public function setTranscribedAt(?int $transcribedAt): self
    {
        $this->transcribedAt = $transcribedAt;
        return $this;
    }

    /**
     * getmainlanguagecode
     */
    public function getPrimaryLanguage(): ?string
    {
        return $this->primaryLanguage ?? null;
    }

    /**
     * setmainlanguagecode
     */
    public function setPrimaryLanguage(?string $primaryLanguage): self
    {
        $this->primaryLanguage = $primaryLanguage;
        return $this;
    }

    /**
     * fromarraycreate实例.
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * checkwhetherfor空（nothave任何转录result）.
     */
    public function isEmpty(): bool
    {
        return empty($this->transcriptions);
    }

    /**
     * clear所have转录result.
     */
    public function clear(): self
    {
        $this->transcriptions = null;
        $this->errorMessage = null;
        $this->transcribedAt = null;
        return $this;
    }
}
