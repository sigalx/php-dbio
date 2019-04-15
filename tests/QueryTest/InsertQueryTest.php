<?php
/** @noinspection PhpUnhandledExceptionInspection */

class InsertQueryTest extends \PHPUnit\Framework\TestCase
{
    public function testTest1()
    {
        $query = new \sigalx\dbio\Query\InsertQuery('example_table', [
            'id',
            'name',
            'male',
        ]);
        $this->assertRegExp('/[0-9a-z]{4}/', $query->getUniqid());
        $this->assertEquals('INSERT INTO `example_table` (`id`,`name`,`male`) VALUES (:id,:name,:male)', $query->getSql());
        $this->assertEquals([], $query->getParams());
    }

    public function testTest2()
    {
        $query = new \sigalx\dbio\Query\InsertQuery('example_table');
        $query->addRow([
            'id' => 13,
            'name' => 'Ivan',
            'male' => true,
        ]);
        $this->assertRegExp('/[0-9a-z]{4}/', $query->getUniqid());
        $this->assertEquals("INSERT INTO `example_table` (`id`,`name`,`male`) VALUES (:__{$query->getUniqid()}_0_id,:__{$query->getUniqid()}_1_name,:__{$query->getUniqid()}_2_male)", $query->getSql());
        $this->assertEquals([
            "__{$query->getUniqid()}_0_id" => 13,
            "__{$query->getUniqid()}_1_name" => 'Ivan',
            "__{$query->getUniqid()}_2_male" => true,
        ],
            $query->getParams()
        );
    }

    public function testTest3()
    {
        $query = new \sigalx\dbio\Query\InsertQuery('example_table');
        $query->addRow([
            'id' => 13,
            'name' => 'Ivan',
            'male' => true,
        ])->addRow([
            'id' => 14,
            'name' => 'Mariya',
            'male' => false,
        ]);
        $this->assertRegExp('/[0-9a-z]{4}/', $query->getUniqid());
        $this->assertEquals("INSERT INTO `example_table` (`id`,`name`,`male`) VALUES (:__{$query->getUniqid()}_0_id,:__{$query->getUniqid()}_1_name,:__{$query->getUniqid()}_2_male),(:__{$query->getUniqid()}_3_id,:__{$query->getUniqid()}_4_name,:__{$query->getUniqid()}_5_male)", $query->getSql());
        $this->assertEquals([
            "__{$query->getUniqid()}_0_id" => 13,
            "__{$query->getUniqid()}_1_name" => 'Ivan',
            "__{$query->getUniqid()}_2_male" => true,
            "__{$query->getUniqid()}_3_id" => 14,
            "__{$query->getUniqid()}_4_name" => 'Mariya',
            "__{$query->getUniqid()}_5_male" => false,
        ],
            $query->getParams()
        );
    }

}
