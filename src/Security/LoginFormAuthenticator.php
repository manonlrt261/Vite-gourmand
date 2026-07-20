<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function supports(Request $request): bool
    {
        if ($request->isMethod('POST') && $request->attributes->get('_route') === 'login') {
            return true;
        }

        // Reprend sans rupture les sessions créées par l'inscription et par l'ancienne authentification.
        return !$request->getUser()
            && is_array($request->getSession()->get('utilisateur'))
            && (string) ($request->getSession()->get('utilisateur')['email'] ?? '') !== '';
    }

    public function authenticate(Request $request): Passport
    {
        if ($request->attributes->get('_route') !== 'login') {
            $legacyUser = $request->getSession()->get('utilisateur');

            return new SelfValidatingPassport(new UserBadge((string) $legacyUser['email']));
        }

        $email = trim((string) $request->request->get('email'));
        $request->getSession()->set('_security.last_username', $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials((string) $request->request->get('password')),
            [
                new CsrfTokenBadge('authenticate', (string) $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        if ($user instanceof SecurityUser) {
            // Compatibilité avec les contrôleurs existants pendant leur migration vers getUser().
            $request->getSession()->set('utilisateur_id', $user->getId());
            $request->getSession()->set('utilisateur', $user->toLegacySession());
        }

        if ($request->attributes->get('_route') !== 'login') {
            return null;
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $requestedTarget = (string) $request->query->get('target');
        if (str_starts_with($requestedTarget, '/') && !str_starts_with($requestedTarget, '//')) {
            return new RedirectResponse($requestedTarget);
        }

        $roles = $user instanceof SecurityUser ? $user->getRoles() : [];
        $route = match (true) {
            in_array('ROLE_ADMIN', $roles, true) => 'admin_dashboard',
            in_array('ROLE_EMPLOYEE', $roles, true) => 'employee_dashboard',
            default => 'customer_account',
        };

        return new RedirectResponse($this->urlGenerator->generate($route));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->getFlashBag()->add('error', 'Email ou mot de passe incorrect.');

        return new RedirectResponse($this->getLoginUrl($request));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('login');
    }
}
