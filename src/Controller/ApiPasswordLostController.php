<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\TokenInit;
use App\Entity\Token;
use App\Entity\User;
use App\Entity\UserPasswordLost;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
class ApiPasswordLostController extends TokenInit
{

    #[Route(
        '/api/{_locale<%app.supported_locales%>}/password-lost',
        name: 'app_api_password_lost',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => UserPasswordLost::class,
            '_api_item_operation_name' => 'app_api_password_lost',
        ],
    )]
    public function __invoke(
        Request $request,
        ManagerRegistry $doctrine,
        MailerInterface $mailer,
        TranslatorInterface $translator
    ): Response {

        // mapping
        $identifier = new UserPasswordLost();
        $identifier->mapFromArray($request->toArray());

        $userRep = $doctrine->getRepository(User::class);
        $users = $userRep->findByIdentifier($identifier->getIdentifier());

        if (is_array($users) && isset($users[0])) {
            $user = $users[0];

            try {
                $this->sendEmail(
                    $mailer,
                    $user->getEmail(),
                    $this->initToken($user, $doctrine, 'password', '15 minutes'),
                    $translator
                );
            } catch (TransportExceptionInterface $ex) {
                return $this->error(
                    CodeError::EMAIL_UNAVAILABLE,
                    "Cannot send email",
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }

        // say nothing if it is not found.
        return $this->OK();
    }

    public function sendEmail(MailerInterface $mailer, string $mail, Token $token, TranslatorInterface $translator)
    {

        $link = str_replace(
            ':token',
            $token->getToken(),
            $this->getParameter('client.url.password.lost')
        );

        $email = (new Email())
            ->from('no-reply@ikilote.net')
            ->to($mail)
            ->subject($translator->trans('mail.password.lost.object'))
            ->text($translator->trans('mail.password.lost.intro') . "\n\n" . $link)
            ->html('<p>' . $translator->trans('mail.password.lost.intro') . '</p>'
                . '<p><a href="' . $link . '">' . $link . '</a></p>');

        $mailer->send($email);
    }
}
