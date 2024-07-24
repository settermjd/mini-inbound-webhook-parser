<?php

declare(strict_types=1);

namespace App;

use JustSteveKing\StatusCode\Http;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProcessEmailHandler
{
    public const IS_VALID_SUBJECT = 1;

    public const VALID_SUBJECT_REGEX = "/(?i:Ref(erence)? ID: )(?<refid>[0-9a-zA-Z]{14})/";

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
        $status = Http::OK;

        if (! $this->isValidSubjectLine($subject)) {
            $responseData = [
                "status" => "error",
                "message" => "The email subject does not contain a valid reference ID.",
                "detail" => "Email subject lines must match one of the following two, case-insensitive, formats: 'Reference ID: REF_ID' or 'Ref ID: REF_ID'. REF_ID is a 14 character string. It can contain lower and uppercase letters from A to Z (inclusive), and any digit between 0 and 9 (inclusive).",
            ];
            $response->getBody()->write(json_encode($responseData));
            $status = Http::BAD_REQUEST;
        }

        $response->withHeader('Content-Type', 'application/json');
        return $response->withStatus($status->value);
    }
}