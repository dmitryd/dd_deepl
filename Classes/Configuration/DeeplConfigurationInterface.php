<?php

namespace Dmitryd\DdDeepl\Configuration;

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

/**
 * This class contains a contract for resolved DeepL configuration values.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
interface DeeplConfigurationInterface
{
    /**
     * Fetches an identifier for runtime caches.
     *
     * @return string
     */
    public function getCacheIdentifier(): string;

    /**
     * Fetches DeepL API host.
     *
     * @return string
     */
    public function getApiUrl(): string;

    /**
     * Fetches DeepL API key.
     *
     * @return string
     */
    public function getApiKey(): string;

    /**
     * Fetches the glossary for language pairs.
     *
     * @param string $sourceLanguage
     * @param string $targetLanguage
     * @return ?string
     */
    public function getGlossaryForLanguagePair(string $sourceLanguage, string $targetLanguage): ?string;

    /**
     * Fetches maximum number of glossaries per language pair.
     *
     * @return int
     */
    public function getMaximumNumberOfGlossaries(): int;

    /**
     * Returns the timeout in seconds.
     *
     * @return int
     */
    public function getTimeout(): int;

    /**
     * Checks if DeepL is configured in TYPO3.
     *
     * @return bool
     */
    public function isConfigured(): bool;
}
