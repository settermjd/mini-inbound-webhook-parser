<?php
declare(strict_types=1);

namespace App;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Sql\Sql;

readonly class DatabaseHandler
{
    public function __construct(private AdapterInterface $adapter){}

    public function insertNote(int $userID, string $note): int
    {
        $sql = new Sql($this->adapter);
        $insert = $sql->insert();
        $insert
            ->into('note')
            ->columns(['details', 'user_id'])
            ->values([$note, $userID]);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $statement->execute();

        return (int) $this->adapter
            ->getDriver()
            ->getLastGeneratedValue();
    }

    public function insertAttachment(int $noteID, $attachment): int
    {
        $sql = new Sql($this->adapter);
        $insert = $sql->insert();
        $insert
            ->into('attachment')
            ->columns(['file'])
            ->values([$attachment]);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $statement->execute();

        return (int) $this->adapter
            ->getDriver()
            ->getLastGeneratedValue();
    }
}