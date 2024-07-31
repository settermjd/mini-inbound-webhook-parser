<?php
declare(strict_types=1);

namespace AppTest;

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class DatabaseHandlerTest extends TestCase
{
    use DatabaseBackedTestTrait;

    public function testCanCreateNewNote()
    {
        $this->assertSame(2,
            $this->handler->insertNote(1, "Here is my note")
        );
    }

    public function testCanFindUserFromEmailAddress()
    {
        $this->assertSame(
            [
                'id' => 1,
                'name' => 'Billy Joel',
                'email' => 'example@example.org',
                'phone_number' => '+11234567890',
            ],
            $this->handler->findUserByEmailAddress('example@example.org')
        );
    }

    #[TestWith(['MSAU2407240002', false])]
    #[TestWith(['MSAU2407240001', true])]
    public function testCanValidateReferenceIDs(string $referenceID, bool $isValid): void
    {
        $result = $this->handler->isValidReferenceID($referenceID);
        ($isValid)
            ? $this->assertTrue($result)
            : $this->assertFalse($result);
    }

    public function testCannotFindUserFromEmailAddressWhenNoEmailAddressIsExists()
    {
        $this->assertNull(
            $this->handler->findUserByEmailAddress(
                'unknown.user@example.org'
            )
        );
    }

    #[Depends('testCanCreateNewNote')]
    public function testCanCreateNewAttachment()
    {
        $attachment = file_get_contents(
            __DIR__ . "/data/attachment/generic-document.pdf"
        );
        $this->assertSame(
            1,
            $this->handler->insertAttachment(
                1,
                $attachment,
                'DockMcWordface.docx',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            )
        );
    }

    public function testCanRetrieveNoteByID(): void
    {
        $noteDetails = [
            'id' => 1,
            'user_id' => 1,
            'details' => 'Here are the details of the note',
        ];
        $this->assertSame($noteDetails, $this->handler->getNoteByID(1));
    }

    public function testCannotRetrieveNoteWithIDThatDoesNotExist(): void
    {
        $this->assertNull($this->handler->getNoteByID(11));
    }
}