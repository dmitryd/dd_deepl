<?php

namespace Dmitryd\DdDeepl\Localization;

/***************************************************************
*  Copyright notice
*
*  (c) 2026 Dmitry Dulepov <dmitry.dulepov@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use TYPO3\CMS\Core\SingletonInterface;

/**
 * This class contains a process-local marker for DeepL localization runs.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class DeepLLocalizationScope implements SingletonInterface
{
    protected const TRANSLATION_FAILURE_LABEL = 'LLL:EXT:dd_deepl/Resources/Private/Language/locallang.xlf:localization.error.someElementsNotTranslated';

    /** @var string[] */
    protected array $errors = [];

    protected int $translationFailureCount = 0;

    protected int $level = 0;

    /**
     * Runs the callback while DeepL localization is marked as active.
     *
     * @param \Closure $callback
     * @return mixed
     */
    public function run(\Closure $callback): mixed
    {
        $this->enter();
        try {
            $result = $callback();
        } finally {
            $this->leave();
        }

        return $result;
    }

    /**
     * Checks if DeepL localization is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->level > 0;
    }

    /**
     * Adds an error that happened during the current DeepL localization run.
     *
     * @param string $message
     */
    public function addError(string $message): void
    {
        if (!in_array($message, $this->errors, true)) {
            $this->errors[] = $message;
        }
    }

    /**
     * Adds a user-facing error and increments DeepL translation failure count.
     *
     * @param string $message
     */
    public function addTranslationFailure(string $message): void
    {
        ++$this->translationFailureCount;
        $this->addError($message);
    }

    /**
     * Adds the generic translated user-facing DeepL failure message.
     */
    public function addGenericTranslationFailure(): void
    {
        $this->addTranslationFailure($this->getTranslationFailureMessage());
    }

    /**
     * Fetches errors collected during the current DeepL localization run.
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Fetches the number of DeepL translation failures in the current run.
     *
     * @return int
     */
    public function getTranslationFailureCount(): int
    {
        return $this->translationFailureCount;
    }

    /**
     * Marks the start of a DeepL localization scope.
     */
    protected function enter(): void
    {
        if ($this->level === 0) {
            $this->errors = [];
            $this->translationFailureCount = 0;
        }
        ++$this->level;
    }

    /**
     * Marks the end of a DeepL localization scope.
     */
    protected function leave(): void
    {
        $this->level = max(0, $this->level - 1);
    }

    /**
     * Fetches the translated user-facing message for DeepL failures.
     *
     * @return string
     */
    protected function getTranslationFailureMessage(): string
    {
        $languageService = $GLOBALS['LANG'] ?? null;
        $message = $languageService?->sL(self::TRANSLATION_FAILURE_LABEL) ?? '';

        return $message !== '' ? $message : self::TRANSLATION_FAILURE_LABEL;
    }
}
