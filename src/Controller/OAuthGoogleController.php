<?php

namespace App\Controller;

use App\Controller\Common\TokenInit;
use App\Entity\User;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class OAuthGoogleController extends TokenInit
{
    /**
     * Link to this controller to start the "connect" process
     */
    #[Route(
        '/connect/google',
        name: 'connect_google_start'
    )]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        // will redirect to Facebook!
        return $clientRegistry
            ->getClient('google') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'email' // the scopes you want to access
            ], []);
    }

    /**
     * After going to Facebook, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     */
    #[Route(
        '/connect/google/check',
        name: 'connect_google_check'
    )]
    public function connectCheckAction(ClientRegistry $clientRegistry, ManagerRegistry $doctrine)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)

        /** @var \KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient $client */
        $client = $clientRegistry->getClient('google');

        try {
            // the exact class depends on which provider you're using
            $googleUser = $client->fetchUser();

            $tokenRep = $doctrine->getRepository(User::class);
            $user = $tokenRep->findOneBy(['email' => $googleUser->getEmail()]);


            if ($user === null) {
                $user = new User();

                $email = $googleUser->getEmail();
                preg_match("/^(?P<user>.+)@[^@]+$/",  $email, $userEmail);
                $userName = $userEmail['user'];

                do {
                    $userTest = $tokenRep->findOneBy(['username' => $userName]);

                    if ($userTest) {
                        $userName .= random_int(0, 9);
                    }
                } while ($userTest);

                $user->setEmail($email);
                $user->setUsername($userName);
                $user->setRoles(['ROLE_USER']);
                $user->setDateCreate(new DateTime("now"));
                $user->setIsValidated(true);
                $user->setDeleted(false);
                $user->setAvatar(false);

                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
            } elseif (!$user->getIsValidated()) {
                $user->setIsValidated(true);

                $entityManager = $doctrine->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
            }

            $token = $this->initToken($user, $doctrine, 'google', '30 seconds');

            $link = str_replace(
                [':token', ':service'],
                [$token->getToken(), 'google'],
                $this->formatDomain($this->getParameter('client.url.oauth.connect'))
            );

            header("Location: $link");
            die;
        } catch (IdentityProviderException $e) {
            // something went wrong!
            // probably you should return the reason to the user
            var_dump($e->getMessage());
            die;
        }
    }
}
