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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class contains legacy DeepL configuration loaded from TypoScript.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
#[Autoconfigure(public: true)]
class LegacyTypoScriptConfiguration implements DeeplConfigurationInterface
{
    protected string $apiUrl = '';

    protected string $apiKey = '';

    /** @var string[] */
    protected array $glossaries = [];

    protected int $maximumNumberOfGlossaries = 2;

    protected int $timeout = 10;

    /**
     * Creates the instance of this class.
     */
    public function __construct(ConfigurationManagerInterface $configurationManager)
    {
        $this->triggerDeprecation();

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
     * Fetches an identifier for runtime caches.
     *
     * @return string
     */
    #[\Override]
    public function getCacheIdentifier(): string
    {
        return 'legacy-typoscript';
    }

    /**
     * Fetches DeepL API host.
     *
     * @return string
     */
    #[\Override]
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * Fetches DeepL API key.
     *
     * @return string
     */
    #[\Override]
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Fetches the glossary for language pairs.
     *
     * @param string $sourceLanguage
     * @param string $targetLanguage
     * @return ?string
     */
    #[\Override]
    public function getGlossaryForLanguagePair(string $sourceLanguage, string $targetLanguage): ?string
    {
        $key = $sourceLanguage . '-' . $targetLanguage;

        return $this->glossaries[$key] ?? null;
    }

    /**
     * Fetches maximum number of glossaries per language pair.
     *
     * @return int
     */
    #[\Override]
    public function getMaximumNumberOfGlossaries(): int
    {
        return $this->maximumNumberOfGlossaries;
    }

    /**
     * Returns the timeout in seconds.
     *
     * @return int
     */
    #[\Override]
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Checks if DeepL is configured in TYPO3.
     *
     * @return bool
     */
    #[\Override]
    public function isConfigured(): bool
    {
        return !empty($this->getApiKey()) && !empty($this->getApiUrl());
    }

    /**
     * Triggers the deprecation for the legacy configuration source.
     */
    protected function triggerDeprecation(): void
    {
        static $deprecationTriggered = false;

        if (!$deprecationTriggered) {
            trigger_error(
                'EXT:dd_deepl TypoScript configuration is deprecated. Use site configuration key "ddDeepl" instead.',
                E_USER_DEPRECATED
            );
            $deprecationTriggered = true;
        }
    }
}
