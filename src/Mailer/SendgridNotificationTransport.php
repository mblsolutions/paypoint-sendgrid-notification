<?php

namespace MBLSolutions\SendgridNotification\Mailer;

use Illuminate\Support\Str;
use MBLSolutions\SendgridNotification\Exception\MailException;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Transport\TransportInterface;

class SendgridNotificationTransport implements TransportInterface
{
    /** @var TransportInterface */
    private $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {        
        if ($message instanceof Email) {
            // Get the headers from the Email object
            $headers = $message->getHeaders();

            // Create a custom MetadataHeader for unique_email_identifier
            $metadataHeader = new MetadataHeader(
                config('notification.unique_email_identifier'),
                Str::uuid()
            );

            $headers->add($metadataHeader);
        }else{
            // Handle the case where the message is not an Email object
            throw new MailException('Unsupported message type received by SendgridNotificationTransport');
        }
        
        // Delegate the actual sending to the wrapped transport.
        $result = $this->transport->send($message, $envelope);      

        return $result;
    }

    public function __toString(): string
    {
        return 'sendgrid+notification://';
    }
}