<?php

declare(strict_types=1);

namespace App;

use JustSteveKing\StatusCode\Http;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
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
        $noteID = (int) $request->getAttribute('note');
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

        Settings::setPdfRendererOptions([
            'font' => 'Arial'
        ]);
        Settings::setPdfRendererName(Settings::PDF_RENDERER_MPDF);
        Settings::setPdfRendererPath(__DIR__ . '/../vendor/mpdf/mpdf');

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText($note['details']);

        $filename = __DIR__ . '/../tmp/note.pdf';
        $objWriter = IOFactory::createWriter($phpWord, 'PDF');
        $objWriter->save($filename);

        $response
            ->getBody()
            ->write(file_get_contents($filename));

        return $response
            ->withHeader('content-disposition', 'attachment; filename=note.pdf;')
            ->withHeader('content-type', 'application/pdf');
    }
}