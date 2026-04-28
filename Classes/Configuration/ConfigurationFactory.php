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
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains a factory that selects the DeepL configuration source.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
#[Autoconfigure(public: true)]
class ConfigurationFactory
{
    protected const SOURCE_LEGACY_TYPOSCRIPT = 'legacyTypoScript';
    protected const SOURCE_SITE_CONFIGURATION = 'siteConfiguration';

    /**
     * Creates the instance of this class.
     */
    public function __construct(
        protected ExtensionConfiguration $extensionConfiguration,
        protected SiteFinder $siteFinder,
    ) {
    }

    /**
     * Creates the configuration for the given site.
     *
     * @param \TYPO3\CMS\Core\Site\Entity\Site|null $site
     * @return \Dmitryd\DdDeepl\Configuration\DeeplConfigurationInterface
     */
    public function create(?Site $site = null): DeeplConfigurationInterface
    {
        if ($this->getConfigurationSource() === self::SOURCE_LEGACY_TYPOSCRIPT) {
            return GeneralUtility::makeInstance(LegacyTypoScriptConfiguration::class);
        }

        return GeneralUtility::makeInstance(SiteBasedConfiguration::class, $site);
    }

    /**
     * Creates the configuration from the current request.
     *
     * @return \Dmitryd\DdDeepl\Configuration\DeeplConfigurationInterface
     */
    public function createFromRequest(): DeeplConfigurationInterface
    {
        return $this->create($this->getSiteFromRequest());
    }

    /**
     * Fetches a site from the current request if possible.
     *
     * @return \TYPO3\CMS\Core\Site\Entity\Site|null
     */
    public function getSiteFromRequest(): ?Site
    {
        $site = null;
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request) {
            $site = $request->getAttribute('site');
            if (!$site instanceof Site) {
                $site = null;
                $queryParams = $request->getQueryParams() ?? [];
                $pageId = (int)($queryParams['id'] ?? 0);
                if ($pageId > 0) {
                    try {
                        $site = $this->siteFinder->getSiteByPageId($pageId);
                    } catch (SiteNotFoundException) {
                        $site = null;
                    }
                }
            }
        }

        return $site;
    }

    /**
     * Fetches the configured source for DeepL configuration.
     *
     * @return string
     */
    protected function getConfigurationSource(): string
    {
        try {
            $source = (string)$this->extensionConfiguration->get('dd_deepl', 'configurationSource');
        } catch (\Exception) {
            $source = self::SOURCE_SITE_CONFIGURATION;
        }

        return $source === self::SOURCE_LEGACY_TYPOSCRIPT ? $source : self::SOURCE_SITE_CONFIGURATION;
    }
}
