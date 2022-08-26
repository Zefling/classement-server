<?php

namespace App\Controller;

use App\Entity\Contact;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsController]
class ApiContactController extends AbstractApiController
{

    #[Route(
        '/api/contact',
        name: 'app_api_contact',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => Contact::class,
            '_api_item_operations_name' => 'app_api_contact',
        ],
    )]
    public function __invoke(Request $request, MailerInterface $mailer): Response
    {

        $contact = new Contact();
        $contact->mapFromArray($request->toArray());
        try {
            $this->sendEmail($mailer, $contact);

            return $this->OK();
        } catch (TransportExceptionInterface $ex) {
            return $this->error(
                CodeError::EMAIL_UNAVAILABLE,
                "Cannot send email",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function sendEmail(MailerInterface $mailer, Contact $contact)
    {

        $email = (new Email())
            ->from(new Address($contact->getEmail(), $contact->getUsername()))
            ->to($this->getParameter('contact.email'))
            ->subject($this->getParameter('contact.object'))
            ->text($contact->getMessage());

        $mailer->send($email);
    }
}
