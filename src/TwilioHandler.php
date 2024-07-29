<?php
declare(strict_types=1);

namespace App;

use eXorus\PhpMimeMailParser\Attachment;
use Twilio\Rest\Api\V2010\Account\MessageInstance;
use Twilio\Rest\Client;

class TwilioHandler
{
    private string $templateMessageWithSingleAttachment = <<<EOF
Hi %s. This a quick confirmation that "%s" has been added as a note on your account, along with the text, which you can find in the attachment to this SMS.
EOF;

    private string $templateMessageWithAttachments = <<<EOF
Hi %s. This a quick confirmation that "%s" have been added as a note on your account, along with the text, which you can find in the attachment to this SMS.
EOF;

    private array $successfulMessageStatuses;

    /**
     * @param Client $client    The Twilio client used to simplify interacting with Twilio's APIs
     * @param string $sender    The phone number that SMS are sent from
     * @param string $appBaseURL   The base URL of the application used when Twilio downloads note attachments
     */
    public function __construct(
        private readonly Client $client,
        private readonly string $sender,
        private readonly string $appBaseURL,
    ) {
        $this->successfulMessageStatuses = [
            'accepted',
            'delivered',
            'queued',
            'read',
            'received',
            'receiving',
            'scheduled',
            'sending',
            'sent',
            'undelivered',
        ];
    }

    /**
     * @param Attachment[] $attachments
     */
    public function sendNewNoteNotification(
        int    $noteID,
        string $recipientPhoneNumber,
        string $recipientName,
        array  $attachments = [],
    ): bool
    {
        $messageBodyTemplate = (count($attachments) > 1)
            ? $this->templateMessageWithAttachments
            : $this->templateMessageWithSingleAttachment;

        $filenames = implode(', ',
            array_map(
                fn(Attachment $attachment): string => $attachment->getFilename() ?? '',
                $attachments
            )
        );
        $message = $this
            ->client
            ->messages
            ->create(
                $recipientPhoneNumber,
                [
                    "body" => sprintf($messageBodyTemplate, $recipientName, $filenames),
                    "from" => $this->sender,
                    "mediaUrl" => [
                        sprintf(
                            "%s/note/%d",
                            $this->appBaseURL,
                            $noteID
                        ),
                    ]
                ]
            );

        return in_array($message->status, $this->successfulMessageStatuses);
    }
}