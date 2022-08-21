<?php

namespace App\Controller;

use App\Entity\Token;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\UserPasswordLost;
use App\EventSubscriber\TokenSubscriber;
use DateInterval;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
class ApiPasswordLostController extends AbstractApiController implements TokenAuthenticatedController
{
    public function __construct(private TokenSubscriber $tokenSubscriber)
    {
    }

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
        $user = $userRep->findByIdenfier($identifier->getEmailOrUsername());

        if ($user) {

            $token = new Token($user, DateInterval::createFromDateString("15 minutes"), 'password');

            $entityManager = $doctrine->getManager();
            $entityManager->persist($token);
            $entityManager->flush();

            $this->sendEmail($mailer, $user->getEmail(), $token, $translator);
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
