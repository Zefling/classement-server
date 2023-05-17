<?php

namespace App\Controller;

use App\Controller\Common\TokenInit;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Exception\MissingAuthorizationCodeException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OAuthDiscordController extends TokenInit
{
    /**
     * Link to this controller to start the "connect" process
     */
    #[Route(
        '/connect/discord',
        name: 'connect_discord_start'
    )]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        // will redirect to Facebook!
        return $clientRegistry
            ->getClient('discord') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'identify', 'email' // the scopes you want to access
            ], []);
    }

    /**
     * After going to Facebook, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     */
    #[Route(
        '/connect/discord/check',
        name: 'connect_discord_check'
    )]
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, ManagerRegistry $doctrine)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)

        /** @var \KnpU\OAuth2ClientBundle\Client\Provider\DiscordClient $client */
        $client = $clientRegistry->getClient('discord');

        try {
            // the exact class depends on which provider you're using
            $discordUser = $client->fetchUser();

            if (!$discordUser->getVerified()) {
                echo 'No verified account';
                die;
            }

            $tokenRep = $doctrine->getRepository(User::class);
            $user = $tokenRep->findOneBy(['email' => $discordUser->getEmail()]);

            if ($user === null) {
                $user = new User();

                $email = $discordUser->getEmail();
                $userName = $discordUser->getUsername();

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

            $token = $this->initToken($user, $doctrine, 'discord', '30 seconds');

            $link = str_replace(
                [':token', ':service'],
                [$token->getToken(), 'discord'],
                $this->getParameter('client.url.oauth.connect')
            );

            header("Location: $link");
            die;
        } catch (IdentityProviderException | MissingAuthorizationCodeException  $e) {
            // something went wrong!
            // probably you should return the reason to the user

            header("Location: " . $this->getParameter('client.url.user.login'));
            die;
        }
    }
}
