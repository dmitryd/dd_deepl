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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * This class contains a DeepL configuration loaded from site configuration.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class SiteBasedConfiguration implements DeeplConfigurationInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected string $apiUrl = '';

    protected string $apiKey = '';

    /** @var string[] */
    protected array $glossaries = [];

    protected int $maximumNumberOfGlossaries = 2;

    protected int $timeout = 30;

    protected string $cacheIdentifier = 'site-configuration-none';

    protected bool $apiKeyContainsUnresolvedEnvPlaceholder = false;

    /**
     * Creates the instance of this class.
     */
    public function __construct(protected ?Site $site)
    {
        if ($this->site !== null) {
            $this->cacheIdentifier = 'site-configuration-' . $this->site->getIdentifier();
            $configuration = $this->site->getRawConfiguration()['ddDeepl'] ?? [];
            if (is_array($configuration)) {
                $this->apiKey = $this->resolveApiKey((string)($configuration['apiKey'] ?? ''));
                $this->apiUrl = str_ends_with($this->apiKey, ':fx') ? 'https://api-free.deepl.com' : 'https://api.deepl.com';
                $this->maximumNumberOfGlossaries = (int)($configuration['maximumNumberOfGlossariesPerLanguage'] ?? 2);
                $this->glossaries = is_array($configuration['glossaries'] ?? null) ? $configuration['glossaries'] : [];
                $this->timeout = min(60, max((int)($configuration['timeout'] ?? 30), 3));
            }
        }
    }

    /**
     * Fetches an identifier for runtime caches.
     *
     * @return string
     */
    #[\Override]
    public function getCacheIdentifier(): string
    {
        return $this->cacheIdentifier;
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
        $this->reportUnresolvedApiKey();

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
     * Resolves the API key from site configuration.
     *
     * @param string $apiKey
     * @return string
     */
    protected function resolveApiKey(string $apiKey): string
    {
        if (preg_match('/^%env\\([^)]+\\)%$/', $apiKey)) {
            $this->apiKeyContainsUnresolvedEnvPlaceholder = true;
            $apiKey = '';
        }

        return $apiKey;
    }

    /**
     * Reports an unresolved environment placeholder once per site.
     */
    protected function reportUnresolvedApiKey(): void
    {
        static $reportedSites = [];

        $siteIdentifier = $this->site?->getIdentifier() ?? '';
        if ($this->apiKeyContainsUnresolvedEnvPlaceholder && !isset($reportedSites[$siteIdentifier])) {
            $this->logger?->notice(
                sprintf(
                    'DeepL API key for site "%s" still contains an unresolved environment placeholder.',
                    $siteIdentifier
                )
            );
            $reportedSites[$siteIdentifier] = true;
        }
    }
}
