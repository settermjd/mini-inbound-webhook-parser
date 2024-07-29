<?php
declare(strict_types=1);

namespace AppTest;

use App\TwilioHandler;
use eXorus\PhpMimeMailParser\Attachment;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Api\V2010\Account\MessageList;
use Twilio\Rest\Client;

class TwilioHandlerTest extends TestCase
{
    public function testCanSendNewNoteNotification(): void
    {
        $baseUrl = 'https://localhost:8080';
        $bodyTemplate = 'Hi %s. This a quick confirmation that "%s" has been added as a note on your account, along with the text, which you can find in the attachment to this SMS.';
        $filenames = "file1.docx";
        $fullName = "The Sender";
        $noteID = 1;
        $sender = '+1411158111';
        $recipient = '+1400058000';
        $attachments = [
            new Attachment(
                "file1.docx",
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                $this->createMock(StreamInterface::class),
            ),
        ];

        $message = $this->createMock(MessageInstance::class);
        $message
            ->expects($this->once())
            ->method('__get')
            ->with('status')
            ->willReturn('scheduled');

        $messages = $this->createMock(MessageList::class);
        $messages
            ->expects($this->once())
            ->method('create')
            ->with(
                $recipient,
                [
                    "body" => sprintf($bodyTemplate, $fullName, $filenames),
                    "from" => $sender,
                    "mediaUrl" => [sprintf("%s/note/%d", $baseUrl, $noteID)],
                ]
            )
            ->willReturn($message);

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('__get')
            ->with('messages')
            ->willReturn($messages);

        $handler = new TwilioHandler($client, $sender, $baseUrl);

        $this->assertTrue($handler->sendNewNoteNotification(
            $noteID, $recipient, $fullName, $attachments
        ));
    }
}