<?php

declare(strict_types=1);

namespace Dmitryd\DdDeepl\EventListener;

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

use TYPO3\CMS\Backend\Controller\Event\BeforeBackendPageRenderEvent;
use TYPO3\CMS\Backend\Controller\Event\ModifyPageLayoutContentEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * This class contains a listener that loads DeepL localization wizard warnings.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class LoadLocalizationWizardWarnings
{
    /**
     * Creates the instance of this class.
     */
    public function __construct(protected PageRenderer $pageRenderer)
    {
    }

    /**
     * Adds the warning module to the page module where the localization wizard
     * is started by editors.
     */
    #[AsEventListener(event: ModifyPageLayoutContentEvent::class)]
    public function loadInPageModule(): void
    {
        $this->loadModule();
    }

    /**
     * Adds the warning module to the main backend document where the modal
     * localization wizard is rendered.
     */
    #[AsEventListener(event: BeforeBackendPageRenderEvent::class)]
    public function loadInBackend(): void
    {
        $this->loadModule();
    }

    /**
     * Loads the JavaScript module that adds the warning to the wizard summary.
     */
    protected function loadModule(): void
    {
        $this->pageRenderer->loadJavaScriptModule('@dmitryd/dd-deepl/localization-wizard-warnings.js');
    }
}
