<?php
declare(strict_types=1);

namespace App;

use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Sql\Sql;
use PDO;

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

    public function insertAttachment(
        int $noteID,
        $attachment,
        string $filename,
        string $filetype
    ): int
    {
        $sql = new Sql($this->adapter);
        $insert = $sql->insert();
        $insert
            ->into('attachment')
            ->columns(['note_id', 'file', 'filename', 'filetype'])
            ->values([
                $noteID,
                $attachment,
                $filename,
                $filetype,
            ]);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $statement->execute();

        return (int) $this->adapter
            ->getDriver()
            ->getLastGeneratedValue();
    }

    public function findUserIDByEmailAddress(string $emailAddress): ?int
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select
            ->from('user')
            ->columns(['id'])
            ->where(['email' => $emailAddress]);

        $statement = $sql->prepareStatementForSqlObject($select);
        /** @var ResultInterface|Result $result */
        $result = $statement->execute();

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $record = $result->current();
            return $record['id'] ?? null;
        }

        return null;
    }
}