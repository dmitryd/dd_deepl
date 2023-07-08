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
 * This event will fire for each translatable field before the record
 * is translated to do any necessary pre-processing of the field value.
 * Possible usage: remove some html tags, etc.
 *
 * @author Dmitry Dulepov <support@snowflake.ch>
 */
class PreprocessFieldValueEvent
{
    /**
     * Creates the instance of the class.
     *
     * @param string $tableName
     * @param string $fieldName
     * @param string $fieldValue
     */
    public function __construct(protected string $tableName, protected string $fieldName, protected string $fieldValue)
    {
    }

    /**
     * Fetches the table name for this event.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Fetches the field name for this event.
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Fetches the field value for this event.
     *
     * @return string
     */
    public function getFieldValue(): string
    {
        return $this->fieldValue;
    }

    /**
     * Sets the field value for this event.
     *
     * @param string $fieldValue
     */
    public function setFieldValue(string $fieldValue): void
    {
        $this->fieldValue = $fieldValue;
    }
}
