<?php

declare(strict_types=1);

namespace App;

use JustSteveKing\StatusCode\Http;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class GetNoteMessageBodyHandler
{
    public function __construct(private DatabaseHandler $databaseHandler)
    {}

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
) : ResponseInterface
    {
        $noteID = $request->getAttribute('note');
        $note = $this->databaseHandler->getNoteByID($noteID);
        if (is_null($note)) {
            $response
                ->getBody()
                ->write(json_encode([
                    "message" => "Note not found.",
                    "detail" => "No note with note ID {$noteID} was found.",
                ]));

            return $response
                ->withHeader('content-type', 'application/json; charset=utf-8');
        }

        $response
            ->getBody()
            ->write($note['details']);

        return $response
            ->withHeader('content-disposition', 'attachment; filename=note.txt;')
            ->withHeader('content-type', 'text/plain; charset=utf-8');
    }
}