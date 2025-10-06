<?php

namespace Dmitryd\DdDeepl\Configuration;

/***************************************************************
*  Copyright notice
*
*  (c) 2023 Dmitry Dulepov <dmitry.dulepov@gmail.com>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class contains configuration methods.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class Configuration
{
    protected string $apiUrl = '';

    protected string $apiKey = '';

    /** @var string[] */
    protected array $glossaries = [];

    protected int $maximumNumberOfGlossaries = 2;

    protected int $timeout = 10;

    /**
     * Creates the instance of the class.
     */
    public function __construct(ConfigurationManagerInterface $configurationManager)
    {
        $ts = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $ts = $ts['module.']['tx_dddeepl.'] ?? [];

        if (!isset($ts['settings.']['apiKey.']) || !is_array($ts['settings.']['apiKey.'])) {
            $this->apiKey = $ts['settings.']['apiKey'] ?? '';
        } else {
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $this->apiKey = $contentObject->stdWrap(
                $ts['settings.']['apiKey'] ?? '',
                $ts['settings.']['apiKey.'],
            );
        }
        $this->apiUrl = str_ends_with($this->apiKey, ':fx') ? 'https://api-free.deepl.com' : 'https://api.deepl.com';
        if ($ts['settings.']['maximumNumberOfGlossariesPerLanguage'] ?? false) {
            $this->maximumNumberOfGlossaries = (int)$ts['settings.']['maximumNumberOfGlossariesPerLanguage'];
        }

        $this->glossaries = $ts['settings.']['glossaries.'] ?? [];
        $this->timeout = min(60, max((int)($ts['settings.']['timeout'] ?? 10), 3));
    }

    /**
     * Fetches DeepL API host.
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * Fetches DeepL API key.
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Fetches the glossary for language pairs.
     *
     * @param string $sourceLangage
     * @param string $targetLangage
     * @return ?string
     */
    public function getGlossaryForLanguagePair(string $sourceLangage, string $targetLangage): ?string
    {
        $key = $sourceLangage . '-' . $targetLangage;

        return $this->glossaries[$key] ?? null;
    }

    /**
     * Fetches maximum number of glossaries per language pair.
     *
     * @return int
     */
    public function getMaximumNumberOfGlossaries(): int
    {
        return $this->maximumNumberOfGlossaries;
    }

    /**
     * Returns the timeout in seconds.
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Checks if DeepL is configured in TYPO3.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->getApiKey()) && !empty($this->getApiUrl());
    }
}
