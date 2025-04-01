<?php

namespace Dmitryd\DdDeepl\Event;

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

/**
 * This event will fire to give other extensions an opportunity to post-evaluate
 * and change the condition about translating the field of the record. Initial
 * states are:
 * - true = yes
 * - false = no
 * - null = no idea
 * You can only set true or false with this event.
 *
 * @author Dmitry Dulepov <dmitry.dulepov@gmail.com>
 */
class CanFieldBeTranslatedCheckEvent
{
    /**
     * Creates the instance of the class.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param bool|null $canBeTranslated
     */
    public function __construct(protected string $tableName, protected string $fieldName, protected ?string $fieldValue, protected ?bool $canBeTranslated)
    {
    }

    /**
     * Fetches the table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Fetches the field name.
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Fetches the field value.
     *
     * @return ?string
     */
    public function getFieldValue(): ?string
    {
        return $this->fieldValue;
    }

    /**
     * Fetches the flag that says if the event can be translated.
     *
     * @return ?bool
     */
    public function getCanBeTranslated(): ?bool
    {
        return $this->canBeTranslated;
    }

    /**
     * Sets the flag that says if the event can be translated. You should only
     * call this method if you really know "yes" or "no" answer. No "unknown" (null)
     * is permitted here.
     *
     * @param mixed $canBeTranslated
     */
    public function setCanBeTranslated(bool $canBeTranslated): void
    {
        $this->canBeTranslated = $canBeTranslated;
    }
}
