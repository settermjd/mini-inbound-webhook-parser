<?php
declare(strict_types=1);

namespace App;

use eXorus\PhpMimeMailParser\Attachment;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use PDO;

readonly class DatabaseHandler
{
    public function __construct(private AdapterInterface $adapter){}

    /**
     * insertNote creates a new note record with the note details supplied
     * ($note) and links it to the user using the user's ID ($userID).
     */
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

    /**
     * insertAttachment creates a new attachment record with the attachment details
     * supplied (Attachment) and links it to the note using the note's ID ($noteID).
     */
    public function insertAttachment(int $noteID, Attachment $attachment): int
    {
        $sql = new Sql($this->adapter);
        $insert = $sql->insert();
        $insert
            ->into('attachment')
            ->columns(['note_id', 'file', 'filename', 'filetype'])
            ->values([
                $noteID,
                $attachment->getContent(),
                $attachment->getFilename(),
                $attachment->getContentType(),
            ]);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $statement->execute();

        return (int) $this->adapter
            ->getDriver()
            ->getLastGeneratedValue();
    }

    /**
     * findUserByEmailAddress attempts to retrieve a user's details based on
     * the email address supplied ($emailAddress). If a user cannot be found,
     * with that email address then null is returned.
     *
     * @return array<string,string|int>|null
     */
    public function findUserByEmailAddress(string $emailAddress): ?array
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select
            ->from('user')
            ->where(['email' => $emailAddress]);

        $statement = $sql->prepareStatementForSqlObject($select);
        /** @var ResultInterface|Result $result */
        $result = $statement->execute();

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $record = $result->current();
            return (is_array($record)) ? $record : null;
        }

        return null;
    }

    /**
     * getNoteByID retrieves details of a note based on its id ($noteID)
     * If a note is not found, based on the supplied id, then null is returned.
     * If a note is found, then the note's details are returned in an array.
     *
     * @return null|array<string,string>
     */
    public function getNoteByID(int $noteID): ?array
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select
            ->from('note')
            ->where(['id' => $noteID]);

        $statement = $sql->prepareStatementForSqlObject($select);
        /** @var ResultInterface|Result $result */
        $result = $statement->execute();

        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $record = $result->current();
            return is_array($record) ? $record : null;
        }

        return null;
    }

    /**
     * isValidReferenceID checks if the supplied reference ID supplied($reference)
     * both exists and is linked to a given user.
     */
    public function isValidReferenceID(string $reference): bool
    {
        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select
            ->from(['u' => 'user'])
            ->columns(['count' => new Expression("COUNT(*)")])
            ->join(['r' => 'reference'], 'u.id = r.user_id', [], Select::JOIN_INNER)
            ->where(['r.reference' => $reference])
            ->limit(1);

        $statement = $sql->prepareStatementForSqlObject($select);

        /** @var ResultInterface|Result $result */
        $result = $statement->execute();
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            return (bool) $result->current()['count'] ?? false;
        }

        return false;
    }

}