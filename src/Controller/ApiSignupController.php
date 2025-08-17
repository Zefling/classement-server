<?php

namespace App\Controller;

use App\Controller\Common\CodeError;
use App\Controller\Common\TokenInit;
use App\Entity\Token;
use App\Entity\User;
use DateTime;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class ApiSignupController extends TokenInit
{

    // required API Platform 3.x
    public static function getName(): string
    {
        return 'app_api_signup';
    }

    public function __invoke(
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        TranslatorInterface $translator
    ): Response {
        $content = $request->toArray();

        $user = new User();

        if (!empty($content['username']) && !empty($username = trim($content['username']))) {
            $userRep = $doctrine->getRepository(User::class);
            $userTest = $userRep->findOneBy(['username' => $content['username']]);
            if (!$userTest) {
                $user->setUsername($username);
            } else {
                return  $this->error(
                    CodeError::LOGIN_ALREADY_EXISTS,
                    'This username already exists',
                    Response::HTTP_CONFLICT
                );
            }
        } else {
            return  $this->error(CodeError::LOGIN_MISSING, 'No username');
        }

        if (!empty($content['password']) && strlen($password = trim($content['password'])) > 8) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $password
            );
            $user->setPassword($hashedPassword);
        } else {
            return  $this->error(CodeError::PASSWORD_MISSING, 'No password or valid password');
        }

        if (!empty($content['email']) && filter_var($email = trim($content['email']), FILTER_VALIDATE_EMAIL)) {
            $userRep = $doctrine->getRepository(User::class);
            $userTest = $userRep->findOneBy(['email' => $content['email']]);
            if (!$userTest) {
                $user->setEmail($email);
            } else {
                return  $this->error(
                    CodeError::EMAIL_ALREADY_EXISTS,
                    'This email already exists',
                    Response::HTTP_CONFLICT
                );
            }
        } else {
            return  $this->error(CodeError::EMAIL_MISSING, 'No email or valid email');
        }

        $user->setRoles(['ROLE_USER']);
        $user->setDateCreate(new DateTime("now"));
        $user->setIsValidated(false);
        $user->setDeleted(false);
        $user->setAvatar(false);

        try {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->sendEmail(
                $mailer,
                $content['email'],
                $this->initToken($user, $doctrine, 'validity', '15 minutes'),
                $translator
            );

            return $this->OK();
        } catch (UniqueConstraintViolationException $ex) {
            return $this->error(
                CodeError::DUPLICATE_CONTENT,
                "Duplicate user",
                Response::HTTP_CONFLICT
            );
        } catch (TransportExceptionInterface $ex) {
            return $this->error(
                CodeError::EMAIL_UNAVAILABLE,
                "Cannot send email",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function sendEmail(MailerInterface $mailer, string $mail, Token $token, TranslatorInterface $translator)
    {

        $link = str_replace(
            ':token',
            $token->getToken(),
            $this->formatDomain($this->getParameter('client.url.user.validate'), $this->formatDomain('%domain%'))
        );

        $email = (new Email())
            ->from('no-reply@ikilote.net')
            ->to($mail)
            ->subject($translator->trans('mail.create.user.object'))
            ->text($translator->trans('mail.create.user.intro') . "\n\n" . $link)
            ->html('<p>' . $translator->trans('mail.create.user.intro') . '</p>'
                . '<p><a href="' . $link . '">' . $link . '</a></p>');

        $mailer->send($email);
    }
}
