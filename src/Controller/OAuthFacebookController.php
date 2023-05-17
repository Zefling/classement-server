<?php

namespace App\Controller;

use App\Controller\Common\TokenInit;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\Routing\Annotation\Route;

class OAuthFacebookController extends TokenInit
{
    /**
     * Link to this controller to start the "connect" process
     */
    #[Route(
        '/connect/facebook',
        name: 'connect_facebook_start'
    )]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        // will redirect to Facebook!
        return $clientRegistry
            ->getClient('facebook_main') // key used in config/packages/knpu_oauth2_client.yaml
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
        '/connect/facebook/check',
        name: 'connect_facebook_check'
    )]
    public function connectCheckAction(ClientRegistry $clientRegistry, ManagerRegistry $doctrine)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)

        /** @var \KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient $client */
        $client = $clientRegistry->getClient('facebook_main');

        try {
            // the exact class depends on which provider you're using
            /** @var \League\OAuth2\Client\Provider\FacebookUser $user */
            $facebookUser = $client->fetchUser();

            $tokenRep = $doctrine->getRepository(User::class);
            $user = $tokenRep->findOneBy(['email' => $facebookUser->getEmail()]);

            if ($user === null) {
                $user = new User();

                $email = $facebookUser->getEmail();
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
                $user->setDateCreate(new DateTimeImmutable());
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

            $token = $this->initToken($user, $doctrine, 'facebook', '30 seconds');

            $link = str_replace(
                [':token', ':service'],
                [$token->getToken(), 'facebook'],
                $this->getParameter('client.url.oauth.connect')
            );

            header("Location: $link");
            die;
        } catch (IdentityProviderException | MissingAuthorizationCodeException $e) {
            // something went wrong!
            // probably you should return the reason to the user

            header("Location: " . $this->getParameter('client.url.user.login'));
            die;
        }
    }
}
