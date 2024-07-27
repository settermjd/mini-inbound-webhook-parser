<?php
declare(strict_types=1);

namespace AppTest;

use App\DatabaseHandler;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Sql\Sql;
use mikehaertl\shellcommand\Command;

/**
 * This is a small trait aimed at providing a simple (and simplistic) option
 * for running database integration tests with PHPUnit.
 *
 * It uses SQLite so that there isn't a need to have a database server, such
 * as PostgreSQL or MySQL installed. It sets up and tears down the database
 * before each test.
 */
trait DatabaseBackedTestTrait
{
    private const string DATABASE_PATH = "test/data/database/database.sqlite";

    private DatabaseHandler $handler;
    private AdapterInterface $adapter;

    public function setUp(): void
    {
        // Set up the database
        $command = new Command(
            sprintf(
                'sqlite3 -init %1$s/data/database/dump.sql %1$s/data/database/database.sqlite .quit',
                __DIR__
            )
        );
        echo ($command->execute())
            ? $command->getOutput()
            : $command->getError();

        $this->adapter = new Adapter([
            'driver'   => 'Pdo_Sqlite',
            'database' => self::DATABASE_PATH,
        ]);

        $this->handler = new DatabaseHandler($this->adapter);
    }

    public function tearDown(): void
    {
        // Remove the database
        $command = new Command(sprintf('unlink %s/data/database/database.sqlite', __DIR__));
        echo ($command->execute())
            ? $command->getOutput()
            : $command->getError();
    }

    /**
     * seeInDatabase asserts that there are records matching the criteria supplied in
     * $searchCriteria in the table identified by $tableName.
     */
    public function seeInDatabase(string $tableName, array $searchCriteria): void
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select
            ->from($tableName)
            ->where($searchCriteria);

        $statement = $sql->prepareStatementForSqlObject($select);
        /** @var ResultInterface|Result $result */
        $result = $statement->execute();

        $this->assertTrue($result instanceof ResultInterface);
        $this->assertTrue($result->isQueryResult());
        $this->assertTrue($result->count() > 0);
    }
}