<?php

namespace Dmitryd\DdDeepl\Hook;

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

use Dmitryd\DdDeepl\Service\DeeplTranslationService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class contains a hook to the PageRenderer that injects our own JS module.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class InjectCustomJavascript
{
    /**
     * Injects the module with a DeepL button.
     *
     * @param array $params
     * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
     */
    public function injectCustomJavaScript(array $params, PageRenderer $pageRenderer): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'];
        /** @var ServerRequestInterface $request */
        if (ApplicationType::fromRequest($request)->isBackend() &&
            ($request->getQueryParams()['route'] ?? '') === '/module/web/layout' &&
            GeneralUtility::makeInstance(DeeplTranslationService::class)->isAvailable()
        ) {
            $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/DdDeepl/Localization');
            $pageRenderer->addInlineLanguageLabelFile('EXT:dd_deepl/Resources/Private/Language/locallang.xlf', 'TYPO3.lang.', 'TYPO3.lang.');
        }
    }
}