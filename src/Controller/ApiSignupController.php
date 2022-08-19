<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Entity\UserSingup;
use DateInterval;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
class ApiSignupController extends AbstractApiController
{
    #[Route(
        '/api/{_locale<%app.supported_locales%>}/signup',
        name: 'app_api_signup',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => UserSingup::class,
            '_api_collection_operations_name' => 'app_api_signup',
        ],
    )]
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
                return  $this->error(CodeError::LOGIN_ALREADY_EXISTS, 'This username already exists');
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
                return  $this->error(CodeError::EMAIL_ALREADY_EXISTS, 'This email already exists');
            }
        } else {
            return  $this->error(CodeError::EMAIL_MISSING, 'No email  or valid email');
        }

        $user->setRoles(['ROLE_USER']);
        $user->setDateCreate(new DateTimeImmutable());
        $user->setIsValidated(false);
        $user->setDeleted(false);

        try {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $token = new Token($user, DateInterval::createFromDateString("15 minutes"), 'validity');
            $entityManager->persist($token);
            $entityManager->flush();

            $this->sendEmail($mailer, $content['email'], $token, $translator);

            return $this->OK();
        } catch (UniqueConstraintViolationException $ex) {
            return $this->error(CodeError::DUPLICATE_CONTENT, "Duplicate user");
        }
    }

    public function sendEmail(MailerInterface $mailer, string $mail, Token $token, TranslatorInterface $translator)
    {

        $link = Utils::siteURL() . '/user/validate/' . $token->getToken();

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
