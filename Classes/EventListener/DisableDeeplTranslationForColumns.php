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

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

/**
 * Marks technical columns as non-translatable by DeepL.
 *
 * This runs after the whole TCA is compiled and migrated, so it only ever
 * touches columns that actually exist at that point. Doing it here instead of
 * in Configuration/TCA/Overrides/*.php avoids two problems:
 *
 * - Writing the flag onto a non-existing column used to CREATE a column entry
 *   without the mandatory "config" section, which TYPO3 v14 reports as a
 *   deprecated on-the-fly TCA migration.
 * - The annotation is no longer sensitive to extension loading order: a column
 *   added to one of these tables by another extension is present here
 *   regardless of when that extension's TCA/Overrides ran, so it still gets
 *   excluded from DeepL translation.
 *
 * Tables that are not installed (e.g. tx_news_domain_model_news without
 * EXT:news) are skipped automatically because their columns are absent.
 */
final class DisableDeeplTranslationForColumns
{
    /**
     * @var array<string, list<string>>
     */
    private const NON_TRANSLATABLE_COLUMNS = [
        'pages' => ['cache_tags', 'target', 'TSconfig'],
        'sys_file_reference' => ['fieldname', 'tablenames', 'table_local'],
        'tt_content' => ['target', 'tablenames', 'table_local'],
        'tx_news_domain_model_news' => ['location_simple', 'organizer_simple'],
    ];

    #[AsEventListener(event: AfterTcaCompilationEvent::class)]
    public function __invoke(AfterTcaCompilationEvent $event): void
    {
        $tca = $event->getTca();
        $modified = false;

        foreach (self::NON_TRANSLATABLE_COLUMNS as $table => $fields) {
            foreach ($fields as $field) {
                if (isset($tca[$table]['columns'][$field])) {
                    $tca[$table]['columns'][$field]['translateWithDeepl'] = false;
                    $modified = true;
                }
            }
        }

        if ($modified) {
            $event->setTca($tca);
        }
    }
}
