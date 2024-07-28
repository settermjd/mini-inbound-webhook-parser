<?php

declare(strict_types=1);

namespace App;

use eXorus\PhpMimeMailParser\Parser;
use JustSteveKing\StatusCode\Http;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProcessRequestHandler
{
    public const int IS_VALID_SUBJECT = 1;

    public const string VALID_SUBJECT_REGEX = "/(?i:Ref(erence)? ID: )(?<refid>[0-9a-zA-Z]{14})/";

    public const string VALID_EMAIL_REGEX = "/(?<name>[a-zA-Z\ ]*(?= <)) <(?<address>[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})>/i";

    public function isValidSubjectLine(string $subjectLine): bool
    {
        if ($subjectLine === '') {
            return false;
        }

        $result = preg_match(self::VALID_SUBJECT_REGEX, $subjectLine);
        return $result === self::IS_VALID_SUBJECT;
    }

    public function getReferenceId(string $subjectLine): string
    {
        preg_match(self::VALID_SUBJECT_REGEX, $subjectLine, $matches);
        return $matches['refid'];
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ) {
        $parsedBody = $request->getParsedBody();
        $subject = $parsedBody['subject'] ?? '';

        $response->withHeader('Content-Type', 'application/json');

        if (! $this->isValidSubjectLine($subject)) {
            $responseData = [
                "status" => "error",
                "message" => "The email subject does not contain a valid reference ID.",
                "detail" => "Email subject lines must match one of the following two, case-insensitive, formats: 'Reference ID: REF_ID' or 'Ref ID: REF_ID'. REF_ID is a 14 character string. It can contain lower and uppercase letters from A to Z (inclusive), and any digit between 0 and 9 (inclusive).",
            ];
            $response->getBody()->write(json_encode($responseData));
            
            return $response->withStatus(Http::BAD_REQUEST->value);
        }

        $responseData = [
            "status" => "success",
            "data" => [
                'reference id' => $this->getReferenceId($subject),
            ],
        ];
        $response->getBody()->write(json_encode($responseData));

        return $response;
    }

    public function parseEmail(string $email): array
    {
        $parser = new Parser();
        $parser->setText($email);

        return [
            'sender' => $parser->getHeader('from'),
            // Get all attachments, excluding inline attachments
            'attachments' => $parser->getAttachments(false),
            'message' => [
                'html' => $parser->getMessageBody('html'),
                'text' => $parser->getMessageBody('text')
            ],
        ];
    }

    private function parseEmailAddress(string $emailSender): array
    {
        preg_match(self::VALID_EMAIL_REGEX, $emailSender, $matches);
        return [
            'name' => $matches['name'],
            'address' => $matches['address'],
        ];
    }
}