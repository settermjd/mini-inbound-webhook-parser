<?php

declare(strict_types=1);

namespace App;

use eXorus\PhpMimeMailParser\Attachment;
use eXorus\PhpMimeMailParser\Parser;
use JustSteveKing\StatusCode\Http;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class ProcessRequestHandler
{
    public const int IS_VALID_SUBJECT = 1;

    public const string VALID_SUBJECT_REGEX = "/(?i:Ref(erence)? ID: )(?<refid>[0-9a-zA-Z]{14})/";

    public const string VALID_EMAIL_REGEX = "/(?<name>[a-zA-Z\ ]*(?= <)) <(?<address>[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})>/i";

    public function __construct(
        private readonly DatabaseHandler $databaseHandler,
        private readonly TwilioHandler $twilioHandler,
        private readonly ?LoggerInterface $logger = null
    ){}

    public function isValidSubjectLine(string $subjectLine): bool
    {
        if ($subjectLine === '') {
            return false;
        }

        $result = preg_match(self::VALID_SUBJECT_REGEX, $subjectLine, $matches);
        return (
            $result === self::IS_VALID_SUBJECT
            && $this->databaseHandler->isValidReferenceID($matches['refid']));
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
    ): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $subject = $parsedBody['subject'] ?? '';

        $response->withHeader('Content-Type', 'application/json');

        if (! $this->isValidSubjectLine($subject)) {
            $responseData = [
                "status" => "error",
                "message" => "The email subject either does not contain a valid reference ID or the reference ID supplied is not valid for an existing user.",
                "detail" => "Email subject lines must match one of the following two, case-insensitive, formats: 'Reference ID: REF_ID' or 'Ref ID: REF_ID'. REF_ID is a 14 character string. It can contain lower and uppercase letters from A to Z (inclusive), and any digit between 0 and 9 (inclusive).",
            ];
            $response->getBody()->write(json_encode($responseData));
            
            return $response->withStatus(Http::BAD_REQUEST->value);
        }

        $emailData = $this->parseEmail((string)$parsedBody['email'] ?? '');
        $sender = $this->parseEmailAddress($emailData['sender']);
        $user = $this->databaseHandler->findUserByEmailAddress($sender['address']);
        $noteID = $this->addNote($user['id'], $emailData);

        $this->twilioHandler
            ->sendNewNoteNotification(
                $noteID,
                $user['phone_number'],
                $user['name'],
                $emailData['attachments']
            );

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

    /**
     * addNote adds a new note on the user's account.
     */
    public function addNote($userID, array $emailData): int
    {
        $noteID = $this->databaseHandler->insertNote((int)$userID, $emailData['message']['text']);
        if (count($emailData['attachments'])) {
            $attachments = $emailData['attachments'];
            /** @var Attachment $attachment */
            foreach ($attachments as $attachment) {
                $this->databaseHandler
                    ->insertAttachment($noteID, $attachment);
            }
        }
        return $noteID;
    }
}