<?php
/** @noinspection PhpUnhandledExceptionInspection */

class SelectCountQueryTest extends \PHPUnit\Framework\TestCase
{
    public function testTest1()
    {
        $query = new \sigalx\dbio\Query\SelectCountQuery();
        $query->fromTable('example_table');
        $this->assertEquals('SELECT COUNT(0) FROM `example_table`', $query->getSql());
    }

    public function testTest2()
    {
        $query = new \sigalx\dbio\Query\SelectCountQuery();
        $query->fromTable('example_table');
        $query->addCondition('field1 IS NULL');
        $query->addBetweenCondition('field2', 1, 5);
        $query->addBetweenCondition('field2', 6, 8, true);
        $query->addInCondition('field3', ['abc', 'def', 'ghi']);
        $query->compare('field4', 123, '<>');
        $this->assertRegExp('/[0-9a-z]{4}/', $query->getUniqid());
        $this->assertEquals("SELECT COUNT(0) FROM `example_table` WHERE (field1 IS NULL) AND (`field2` BETWEEN :__{$query->getUniqid()}_0_field2 AND :__{$query->getUniqid()}_1_field2) AND (`field2` NOT BETWEEN :__{$query->getUniqid()}_2_field2 AND :__{$query->getUniqid()}_3_field2) AND (`field3` IN (:__{$query->getUniqid()}_4_field3,:__{$query->getUniqid()}_5_field3,:__{$query->getUniqid()}_6_field3)) AND (`field4` <> :__{$query->getUniqid()}_7_field4)", $query->getSql());
        $this->assertEquals([
            "__{$query->getUniqid()}_0_field2" => 1,
            "__{$query->getUniqid()}_1_field2" => 5,
            "__{$query->getUniqid()}_2_field2" => 6,
            "__{$query->getUniqid()}_3_field2" => 8,
            "__{$query->getUniqid()}_4_field3" => 'abc',
            "__{$query->getUniqid()}_5_field3" => 'def',
            "__{$query->getUniqid()}_6_field3" => 'ghi',
            "__{$query->getUniqid()}_7_field4" => 123,
        ],
            $query->getParams()
        );
    }

    public function testTest3()
    {
        $query = new \sigalx\dbio\Query\SelectCountQuery();
        $query->fromTable('example_table');
        $query->addCondition('field1 IS NULL');
        $query->addBetweenCondition('field2', 1, 5);
        $query->addBetweenCondition('field2', 6, 8, true);
        $query->addInCondition('field3', ['abc', 'def', 'ghi']);
        $query->compare('field4', 123, '<>');
        $query->setOrConditions();
        $this->assertRegExp('/[0-9a-z]{4}/', $query->getUniqid());
        $this->assertEquals("SELECT COUNT(0) FROM `example_table` WHERE (field1 IS NULL) OR (`field2` BETWEEN :__{$query->getUniqid()}_0_field2 AND :__{$query->getUniqid()}_1_field2) OR (`field2` NOT BETWEEN :__{$query->getUniqid()}_2_field2 AND :__{$query->getUniqid()}_3_field2) OR (`field3` IN (:__{$query->getUniqid()}_4_field3,:__{$query->getUniqid()}_5_field3,:__{$query->getUniqid()}_6_field3)) OR (`field4` <> :__{$query->getUniqid()}_7_field4)", $query->getSql());
        $this->assertEquals([
            "__{$query->getUniqid()}_0_field2" => 1,
            "__{$query->getUniqid()}_1_field2" => 5,
            "__{$query->getUniqid()}_2_field2" => 6,
            "__{$query->getUniqid()}_3_field2" => 8,
            "__{$query->getUniqid()}_4_field3" => 'abc',
            "__{$query->getUniqid()}_5_field3" => 'def',
            "__{$query->getUniqid()}_6_field3" => 'ghi',
            "__{$query->getUniqid()}_7_field4" => 123,
        ],
            $query->getParams()
        );
    }

}
