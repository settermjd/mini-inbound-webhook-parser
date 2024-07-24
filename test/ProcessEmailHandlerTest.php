<?php

namespace App;

use JustSteveKing\StatusCode\Http;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;

class ProcessEmailHandlerTest extends TestCase
{
    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[TestWith([''])]
    #[TestWith(['MSAU240724000'])]
    #[TestWith(['MSAU240724000'])]
    #[TestWith(['AU2407240001'])]
    public function testCanDetectInvalidSubjectLines(string $subjectLine)
    {
        $handler = new ProcessEmailHandler();

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getParsedBody')
            ->willReturn([
                'subject' => $subjectLine
            ]);

        $expectedOutput = [
            "status" => "error",
            "message" => "The email subject does not contain a valid reference ID.",
            "detail" => "Email subject lines must match one of the following two, case-insensitive, formats: 'Reference ID: REF_ID' or 'Ref ID: REF_ID'. REF_ID is a 14 character string. It can contain lower and uppercase letters from A to Z (inclusive), and any digit between 0 and 9 (inclusive).",
        ];

        $response = new Response();

        $output = $handler($request, $response, []);
        $output->getBody()->rewind();

        $this->assertInstanceOf(MessageInterface::class, $output);
        $this->assertSame(Http::BAD_REQUEST->value, $output->getStatusCode());
        $this->assertSame(json_encode($expectedOutput), $output->getBody()->getContents());
    }
}