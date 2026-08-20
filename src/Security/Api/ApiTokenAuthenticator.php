<?php

declare(strict_types=1);

namespace App\Security\Api;

use App\Repository\User\ApiPrincipalRepository;
use LogicException;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

use function stripos;
use function strlen;
use function substr;

/**
 * Authenticates API requests with `Authorization: Bearer <token>`.
 *
 * Being the entry point as well as the authenticator keeps the challenge identical for both ways a request can fail
 * to authenticate: a request without a usable token (which never reaches this authenticator) and a request whose
 * token cannot be resolved.
 */
final class ApiTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public const string CHALLENGE = 'Bearer realm="/api"';

    private const string HEADER = 'Authorization';
    private const string SCHEME = 'Bearer ';

    public function __construct(private readonly ApiPrincipalRepository $apiPrincipalRepository)
    {
    }

    #[Override]
    public function supports(Request $request): ?bool
    {
        return null !== $this->extractToken($request);
    }

    #[Override]
    public function authenticate(Request $request): Passport
    {
        $token = $this->extractToken($request);

        if (null === $token) {
            throw new BadCredentialsException('No bearer token was provided.');
        }

        $principal = $this->apiPrincipalRepository->findByToken($token);

        if (null === $principal) {
            throw new BadCredentialsException('The provided bearer token is not known.');
        }

        $user = new ApiPrincipalUser($principal);

        // The token is the entire credential and has already been resolved, so there is nothing left to validate.
        // The badge carries the resolved user rather than the token, keeping the secret out of the user identifier.
        return new SelfValidatingPassport(
            new UserBadge(
                $user->getUserIdentifier(),
                static fn (): UserInterface => $user,
            ),
        );
    }

    #[Override]
    public function createToken(
        Passport $passport,
        string $firewallName,
    ): TokenInterface {
        $user = $passport->getUser();

        if (!($user instanceof ApiPrincipalUser)) {
            throw new LogicException('The API firewall can only authenticate API principals.');
        }

        return new ApiToken(
            $user,
            $firewallName,
        );
    }

    #[Override]
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName,
    ): ?Response {
        return null;
    }

    #[Override]
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception,
    ): ?Response {
        return $this->start(
            $request,
            $exception,
        );
    }

    /**
     * Challenge for requests that did not authenticate; the body is deliberately empty.
     */
    #[Override]
    public function start(
        Request $request,
        ?AuthenticationException $authException = null,
    ): Response {
        return new Response(
            status: Response::HTTP_UNAUTHORIZED,
            headers: ['WWW-Authenticate' => self::CHALLENGE],
        );
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->headers->get(self::HEADER) ?? '';

        // RFC 6750 makes the scheme case-insensitive.
        if (
            0 !== stripos(
                $header,
                self::SCHEME,
            )
        ) {
            return null;
        }

        $token = substr(
            $header,
            strlen(self::SCHEME),
        );

        return '' === $token
            ? null
            : $token;
    }
}
