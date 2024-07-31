<?php
declare(strict_types=1);

namespace AppTest;

use eXorus\PhpMimeMailParser\Attachment;
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
        $attachment = $this->createMock(Attachment::class);
        $attachment
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('Here is a test file');
        $attachment
            ->expects($this->once())
            ->method('getFilename')
            ->willReturn('DockMcWordface.docx');
        $attachment
            ->expects($this->once())
            ->method('getContentType')
            ->willReturn('application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->assertSame(
            1,
            $this->handler->insertAttachment(1, $attachment)
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