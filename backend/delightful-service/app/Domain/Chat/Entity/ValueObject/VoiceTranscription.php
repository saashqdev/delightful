<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

use App\Infrastructure\Core\AbstractValueObject;

/**
 * voicetransfertextresultvalueobject
 * supportmultiplelanguagetranscriptionresultstorage.
 */
class VoiceTranscription extends AbstractValueObject
{
    /**
     * multiplelanguagetranscriptionresult
     * format: ['zh_CN' => 'transcriptiontext', 'en_US' => 'Transcription text', ...].
     * @var null|array<string, string>
     */
    private ?array $transcriptions;

    /**
     * errorinfo(iftranscriptionfail).
     */
    private ?string $errorMessage;

    /**
     * transcriptiontimestamp.
     */
    private ?int $transcribedAt;

    /**
     * mainlanguagecode(defaulttranscriptionlanguage).
     */
    private ?string $primaryLanguage;

    /**
     * get havetranscriptionresult.
     * @return array<string, string>
     */
    public function getTranscriptions(): array
    {
        return $this->transcriptions ?? [];
    }

    /**
     * settranscriptionresult.
     * @param array<string, string> $transcriptions
     */
    public function setTranscriptions(array $transcriptions): self
    {
        $this->transcriptions = $transcriptions;
        return $this;
    }

    /**
     * addsinglelanguagetranscriptionresult.
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
     * getfingersetlanguagetranscriptionresult.
     */
    public function getTranscription(string $language): ?string
    {
        return $this->transcriptions !== null ? ($this->transcriptions[$language] ?? null) : null;
    }

    /**
     * getmainlanguagetranscriptionresult.
     */
    public function getPrimaryTranscription(): ?string
    {
        if ($this->primaryLanguage !== null && $this->transcriptions !== null && isset($this->transcriptions[$this->primaryLanguage])) {
            return $this->transcriptions[$this->primaryLanguage];
        }

        // ifnothavesetmainlanguage,returnfirstcanusetranscriptionresult
        return ! empty($this->transcriptions) ? reset($this->transcriptions) : null;
    }

    /**
     * checkwhetherhavefingersetlanguagetranscriptionresult.
     */
    public function hasTranscription(string $language): bool
    {
        return $this->transcriptions !== null && isset($this->transcriptions[$language]) && ! empty($this->transcriptions[$language]);
    }

    /**
     * get havesupportlanguagecode
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
     * gettranscriptiontimestamp.
     */
    public function getTranscribedAt(): ?int
    {
        return $this->transcribedAt ?? null;
    }

    /**
     * settranscriptiontimestamp.
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
     * fromarraycreateinstance.
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * checkwhetherforempty(nothaveanytranscriptionresult).
     */
    public function isEmpty(): bool
    {
        return empty($this->transcriptions);
    }

    /**
     * clear havetranscriptionresult.
     */
    public function clear(): self
    {
        $this->transcriptions = null;
        $this->errorMessage = null;
        $this->transcribedAt = null;
        return $this;
    }
}
