<?php
declare(strict_types=1);

namespace AppTest;

use App\GetNoteMessageBodyHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Slim\Psr7\Response;

class GetNoteMessageBodyHandlerTest extends TestCase
{
    use DatabaseBackedTestTrait;

    public function testCanRetrieveNoteMessageBodyForExistingNote()
    {
        $handler = new GetNoteMessageBodyHandler($this->handler);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getAttribute')
            ->with('note')
            ->willReturn(1);

        $response = $handler(
            $request,
            new Response(),
            []
        );

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(
            'attachment; filename=note.pdf;',
            $response->getHeaderLine('content-disposition')
        );
        $this->assertSame(
            'application/pdf',
            $response->getHeaderLine('content-type')
        );
    }

    public function testReturnsErrorMessageWhenNoteDoesNotExist(): void
    {
        $handler = new GetNoteMessageBodyHandler($this->handler);

        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getAttribute')
            ->with('note')
            ->willReturn(11);

        $response = $handler(
            $request,
            new Response(),
            []
        );
        $response->getBody()->rewind();

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(
            [
                'message' => 'Note not found.',
                'detail' => 'No note with note ID 11 was found.',
            ],
            json_decode($response->getBody()->getContents(), true)
        );
    }
}