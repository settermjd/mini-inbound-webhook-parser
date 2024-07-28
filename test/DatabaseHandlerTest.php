<?php
declare(strict_types=1);

namespace AppTest;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class DatabaseHandlerTest extends TestCase
{
    use DatabaseBackedTestTrait;

    public function testCanCreateNewNote()
    {
        $this->assertSame(1,
            $this->handler->insertNote(1, "Here is my note")
        );
    }

    public function testCanFindUserIDFromEmailAddress()
    {
        $this->assertSame(
            1,
            $this->handler->findUserIDByEmailAddress('example@example.org')
        );
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