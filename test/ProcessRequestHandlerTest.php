<?php
declare(strict_types=1);

namespace AppTest;

use App\DatabaseHandler;
use App\ProcessRequestHandler;
use App\TwilioHandler;
use eXorus\PhpMimeMailParser\Attachment;
use eXorus\PhpMimeMailParser\Parser;
use JustSteveKing\StatusCode\Http;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class ProcessRequestHandlerTest extends TestCase
{
    use DatabaseBackedTestTrait;

    /**
     * @return void
     * @throws Exception
     */
    #[TestWith([''])]
    #[TestWith(['MSAU240724000'])]
    #[TestWith(['MSAU240724000'])]
    #[TestWith(['AU2407240001'])]
    #[TestWith(['Ref ID: AU2407240001'])]
    #[TestWith(['Reference ID: AU2407240001'])]
    public function testCanDetectInvalidSubjectLines(string $subjectLine)
    {
        $databaseHandler = $this->createMock(DatabaseHandler::class);
        $handler = new ProcessRequestHandler(
            $databaseHandler,
            $this->createMock(TwilioHandler::class),
            null,
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'subject' => $subjectLine
            ]);

        $expectedOutput = [
            "status" => "error",
            "message" => "The email subject either does not contain a valid reference ID or the reference ID supplied is not valid for an existing user.",
            "detail" => "Email subject lines must match one of the following two, case-insensitive, formats: 'Reference ID: REF_ID' or 'Ref ID: REF_ID'. REF_ID is a 14 character string. It can contain lower and uppercase letters from A to Z (inclusive), and any digit between 0 and 9 (inclusive).",
        ];

        $response = new Response();

        $output = $handler($request, $response, []);
        $output->getBody()->rewind();

        $this->assertInstanceOf(MessageInterface::class, $output);
        $this->assertSame(Http::BAD_REQUEST->value, $output->getStatusCode());
        $this->assertSame(json_encode($expectedOutput), $output->getBody()->getContents());
    }

    #[TestWith(['Ref ID: MSAU2407240002'])]
    #[TestWith(['Reference ID: MSAU2407240002'])]
    public function testRejectsReferencesNotLinkedToUserAccounts(string $subjectLine)
    {
        $handler = new ProcessRequestHandler(
            $this->handler,
            $this->createMock(TwilioHandler::class),
            null,
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'subject' => $subjectLine
            ]);

        $expectedOutput = [
            "status" => "error",
            "message" => "The email subject either does not contain a valid reference ID or the reference ID supplied is not valid for an existing user.",
            "detail" => "Email subject lines must match one of the following two, case-insensitive, formats: 'Reference ID: REF_ID' or 'Ref ID: REF_ID'. REF_ID is a 14 character string. It can contain lower and uppercase letters from A to Z (inclusive), and any digit between 0 and 9 (inclusive).",
        ];

        $response = new Response();

        $output = $handler($request, $response, []);
        $output->getBody()->rewind();

        $this->assertInstanceOf(MessageInterface::class, $output);
        $this->assertSame(Http::BAD_REQUEST->value, $output->getStatusCode());
        $this->assertSame(json_encode($expectedOutput), $output->getBody()->getContents());
    }

    #[TestWith(['Ref ID: MSAU2407240001'])]
    #[TestWith(['Reference ID: MSAU2407240001'])]
    public function testCanProcessValidRequests(string $subjectLine)
    {
        $this->seeInDatabase('user', ['email' => 'example@example.org']);

        $emailContents = file_get_contents(sprintf(
            "%s/data/email/sendgrid-example.eml",
            __DIR__
        ));

        $parser = new Parser();
        $parser->setText($emailContents);

        $twilioHandler = $this->createMock(TwilioHandler::class);
        $twilioHandler
            ->expects($this->once())
            ->method('sendNewNoteNotification')
            ->with(
                2,
                '+11234567890',
                'Billy Joel',
                $this->isType('array'),
            )
            ->willReturn(true);

        $requestHandler = new ProcessRequestHandler(
            $this->handler,
            $twilioHandler,
            null,
        );

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'subject' => $subjectLine,
                'email' => $emailContents,
            ]);

        $response = new Response();

        $output = $requestHandler($request, $response, []);
        $output->getBody()->rewind();

        $expectedOutput = [
            'status' => 'success',
            'data' => [
                'reference id' => $requestHandler->getReferenceId($subjectLine),
            ]
        ];

        $this->assertInstanceOf(MessageInterface::class, $output);
        $this->assertSame(Http::OK->value, $output->getStatusCode());
        $this->assertSame(json_encode($expectedOutput), $output->getBody()->getContents());
    }

    public function testCanProcessEmail(): void
    {
        $emailContents = file_get_contents(sprintf(
            "%s/data/email/sendgrid-example.eml",
            __DIR__
        ));
        $databaseHandler = $this->createMock(DatabaseHandler::class);
        $handler = new ProcessRequestHandler(
            $databaseHandler,
            $this->createMock(TwilioHandler::class),
            null,
        );
        $emailData = $handler->parseEmail($emailContents);

        $htmlBody = <<<EOF
<div dir="ltr">This is a test email with 1 attachment.<br clear="all"><div> </div>--  <div class="gmail_signature" data-smartmail="gmail_signature"><div dir="ltr"><img src="https://sendgrid.com/brand/sg-logo-email.png" width="96" height="17"> <div> </div></div></div></div>

EOF;

        $textBody = <<<EOF
This is a test email with 1 attachment.

EOF;

        $this->assertIsArray($emailData);
        $this->assertSame(
            'Sender Name <example@example.org>',
            $emailData['sender']
        );
        $this->assertSame(
            [
                'html' => $htmlBody,
                'text' => $textBody,
            ],
            $emailData['message']
        );
        $this->assertCount(1, $emailData['attachments']);
        $this->assertInstanceOf(Attachment::class, $emailData['attachments'][0]);

        /** @var Attachment $attachment */
        $attachment = $emailData['attachments'][0];
        $this->assertSame("DockMcWordface.docx", $attachment->getFilename());
        $this->assertSame(
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            $attachment->getContentType()
        );
    }
}