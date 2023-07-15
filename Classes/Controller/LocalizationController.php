<?php

namespace Dmitryd\DdDeepl\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LocalizationController extends \TYPO3\CMS\Backend\Controller\Page\LocalizationController
{
    protected const ACTION_DEEPL = 'deepl';

    /**
     * We have to override this because there is a hard-coded check in the original class for allowed actions.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function localizeRecords(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['action'])) {
            return new JsonResponse(null, 400);
        }

        if ($params['action'] === static::ACTION_DEEPL) {
            // Fake it
            $params['action'] = static::ACTION_LOCALIZE;
            $params['deepl'] = true;
        }

        return parent::localizeRecords($request->withQueryParams($params));
    }

    /**
     * Overrides default processing and sends our custom command to the DataHandler.
     *
     * @param $params
     */
    protected function process($params): void
    {
        if (!isset($params['deepl'])) {
            parent::process($params);
        } else {
            $cmd = [
                'tt_content' => [],
            ];

            if (isset($params['uidList']) && is_array($params['uidList'])) {
                foreach ($params['uidList'] as $currentUid) {
                    if ($params['action'] === static::ACTION_LOCALIZE) {
                        $cmd['tt_content'][$currentUid] = [
                            'deepl' => (int)$params['destLanguageId'],
                        ];
                    }
                }
            }

            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([], $cmd);
            $dataHandler->process_cmdmap();
        }
    }
}
