<?php
declare(strict_types=1);
namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class OrderMail{

    /**
     * @var MailerInterface
     */
    private $mailerInterface;

    /**
     * OrderMail constructor.
     * @param MailerInterface $mailerInterface
     */
    public function __construct(MailerInterface $mailerInterface)
    {
        $this->mailerInterface = $mailerInterface;
    }

    public  function sendMail(string $userEmail) {

        $emil = new TemplatedEmail();
        $emil->from(new  Address('contact@foodepia.com','Foodepia Mail'))
            ->subject('New Order')
            ->to($userEmail)
            ->htmlTemplate('frontOffice/emails/orderRestaurant.html.twig');
        try {
            $this->mailerInterface->send($emil);
        } catch (TransportExceptionInterface $e) {
        }
    }
}