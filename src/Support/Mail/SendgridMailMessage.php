<?php

namespace MBLSolutions\SendgridNotification\Support\Mail;

use Symfony\Component\Mime\Email;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Messages\MailMessage;
use Symfony\Component\Mailer\Header\MetadataHeader;

class SendgridMailMessage extends MailMessage {

    public ?User $user;

    /**
     * Create a new message instance.
     */
    public function __construct($user)
    {
        $this->withSymfonyMessage(function (Email $email) use ($user){            
            if ($user) {
                // Create a custom MetadataHeader for auth user
                $metadataHeader = new MetadataHeader(
                    'X-Auth-User-Id',
                    optional($user)->getKey()
                );
                $email->getHeaders()->add($metadataHeader);
            }            
        });

    }

}
