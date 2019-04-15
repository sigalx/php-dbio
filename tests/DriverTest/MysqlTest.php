<?php
/** @noinspection PhpUnhandledExceptionInspection */

class MysqlTest extends \PHPUnit\Framework\TestCase
{
    public function testTest1()
    {
        $mysql = new \sigalx\dbio\Driver\Mysql(['dbname' => 'test', 'username' => 'dev', 'password' => 'dev']);
        $mysql->prepare('DROP TABLE IF EXISTS `test_table`')->execute();
        $mysql->prepare(<<<'EOD'
CREATE TABLE `test_table` (
    `id` BIGINT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `age` INT DEFAULT NULL,
    `male` TINYINT DEFAULT NULL,
    PRIMARY KEY (`id`)
)
EOD
        )
            ->execute();
        $stmt = $mysql->prepareQuery((new \sigalx\dbio\Query\InsertQuery('test_table'))
            ->addRow([
                'name' => 'Ivan',
                'age' => 30,
                'male' => 1,
            ])
            ->addRow([
                'name' => 'Mariya',
                'age' => 25,
                'male' => 0,
            ])
        );
        $this->assertSame(false, $stmt->isExecuted());
        $stmt->execute();
        $this->assertSame(true, $stmt->isExecuted());
        $this->assertSame(2, $stmt->getAffectedRows());

        $stmt = $mysql->prepareQuery((new \sigalx\dbio\Query\InsertQuery('test_table', [
            'name',
            'age',
            'male',
        ])));
        $stmt
            ->reset()
            ->bindParams([
                'name' => 'Petr',
                'age' => 40,
                'male' => 1,
            ])
            ->execute();
        $this->assertSame(true, $stmt->isExecuted());
        $this->assertSame(1, $stmt->getAffectedRows());

        $stmt
            ->reset()
            ->bindParams([
                'name' => 'Sasha',
                'age' => null,
                'male' => null,
            ])
            ->execute();
        $this->assertSame(1, $stmt->getAffectedRows());
        $stmt->execute();
        $this->assertSame(1, $stmt->getAffectedRows());

        $totalCount = $mysql->prepareQuery((new \sigalx\dbio\Query\SelectCountQuery())
            ->fromTable('test_table')
        )
            ->queryScalar();
        $this->assertSame(5, $totalCount);

        $queryResult = $mysql->prepareQuery((new \sigalx\dbio\Query\SelectQuery())
            ->fromTable('test_table')
            ->addBetweenCondition('age', 30, 40)
        )
            ->queryAll();

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'Ivan',
                'age' => 30,
                'male' => 1,
            ],
            [
                'id' => 3,
                'name' => 'Petr',
                'age' => 40,
                'male' => 1,
            ],
        ],
            $queryResult
        );

        $stmt = $mysql->prepareQuery((new \sigalx\dbio\Query\DeleteQuery('test_table'))
            ->compare('name', 'Sasha')
            ->setLimit(1)
        )
            ->execute();
        $this->assertSame(1, $stmt->getAffectedRows());

        $stmt = $mysql->prepareQuery((new \sigalx\dbio\Query\DeleteQuery('test_table'))
            ->compare('age', 30, '<=')
        )
            ->execute();
        $this->assertSame(2, $stmt->getAffectedRows());

        $stmt = $mysql->prepareQuery((new \sigalx\dbio\Query\DeleteQuery('test_table'))
            ->addCondition('male IS NOT NULL')
        )
            ->execute();
        $this->assertSame(1, $stmt->getAffectedRows());

        $stmt = $mysql->prepareQuery((new \sigalx\dbio\Query\UpdateQuery('test_table', ['name']))
            ->addCondition('id = :id')
        );
        $stmt->bindParam('id', 5);
        $stmt->bindParam('name', 'Lesha');
        $stmt->execute();
        $this->assertSame(1, $stmt->getAffectedRows());

        $queryResult = $mysql->prepareQuery((new \sigalx\dbio\Query\SelectQuery())
            ->fromTable('test_table')
        )
            ->queryAll();
        $this->assertSame([[
            'id' => 5,
            'name' => 'Lesha',
            'age' => null,
            'male' => null,
        ]],
            $queryResult
        );
    }

}
