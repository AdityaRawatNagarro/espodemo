<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\unit\Espo\Core\Select\Text;

use Espo\Core\{
    Select\Text\FullTextSearchData,
};

use InvalidArgumentException;

class FullTextSearchDataTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
    }

    public function testFromArray()
    {
        $item = FullTextSearchData::fromArray([
            'expression' => 'TEST',
            'fieldList' => ['test'],
            'columnList' => ['column'],
        ]);

        $this->assertEquals('TEST', $item->getExpression());
        $this->assertEquals(['test'], $item->getFieldList());
        $this->assertEquals(['column'], $item->getColumnList());
    }

    public function testEmpty()
    {
        $item = FullTextSearchData::fromArray([
            'expression' => 'TEST',
        ]);

        $this->assertEquals([], $item->getFieldList());
        $this->assertEquals([], $item->getColumnList());
    }

    public function testNonExistingParam()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = FullTextSearchData::fromArray([
            'bad' => 'd',
        ]);
    }
}
