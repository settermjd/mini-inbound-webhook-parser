<?php
declare(strict_types=1);

namespace AppTest;

use App\DatabaseHandler;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use mikehaertl\shellcommand\Command;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class DatabaseHandlerTest extends TestCase
{
    private const string DATABASE_PATH = "test/data/database/database.sqlite";

    private DatabaseHandler $handler;

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

        $this->handler = new DatabaseHandler(new Adapter([
            'driver'   => 'Pdo_Sqlite',
            'database' => self::DATABASE_PATH,
        ]));
    }

    public function testCanCreateNewNote()
    {
        $this->assertSame(1,
            $this->handler->insertNote(1, "Here is my note")
        );
    }

    public function testCanFindUserIDFromEmailAddress()
    {
        $this->assertSame(1, $this->handler->findUserIDByEmailAddress('b.joel@example.org'));
    }

    public function testCannotFindUserIDFromEmailAddressWhenNoEmailAddressIsExists()
    {
        $this->assertNull($this->handler->findUserIDByEmailAddress('unknown.user@example.org'));
    }

    #[Depends('testCanCreateNewNote')]
    public function testCanCreateNewAttachment()
    {
        $attachment = file_get_contents(
            __DIR__ . "/data/attachment/generic-document.pdf"
        );
        $this->assertSame(1, $this->handler->insertAttachment(1, $attachment));
    }

    public function tearDown(): void
    {
        // Remove the database
        $command = new Command(sprintf('unlink %s/data/database/database.sqlite', __DIR__));
        echo ($command->execute())
            ? $command->getOutput()
            : $command->getError();
    }
}