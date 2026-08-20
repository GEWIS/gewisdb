<?php

declare(strict_types=1);

namespace App\Tests\Security\Api;

use App\Entity\User\ApiPrincipal;
use App\Repository\User\ApiPrincipalRepository;
use App\Security\Api\ApiPrincipalUser;
use App\Security\Api\ApiToken;
use App\Security\Api\ApiTokenAuthenticator;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

#[CoversClass(ApiTokenAuthenticator::class)]
class ApiTokenAuthenticatorTest extends TestCase
{
    /**
     * RFC 6750 makes the scheme case-insensitive, and anything that is not a bearer token belongs to another
     * authenticator rather than to this one.
     */
    #[DataProvider('authorizationHeaders')]
    public function testTakesOnlyRequestsCarryingABearerToken(
        ?string $header,
        bool $supported,
    ): void {
        self::assertSame(
            $supported,
            $this->authenticator()->supports($this->request($header)),
        );
    }

    /**
     * @return array<string, array{string|null, bool}>
     */
    public static function authorizationHeaders(): array
    {
        return [
            'a bearer token' => ['Bearer s3cr3t', true],
            'a lowercase scheme' => ['bearer s3cr3t', true],
            'an uppercase scheme' => ['BEARER s3cr3t', true],
            'no header at all' => [null, false],
            'an empty header' => ['', false],
            'the scheme without a token' => ['Bearer ', false],
            'another scheme' => ['Basic dXNlcjpwYXNz', false],
            // The scheme has to be at the front; a token that merely contains the word is not one.
            'the word further along' => ['Token Bearer s3cr3t', false],
        ];
    }

    public function testResolvesAKnownTokenToItsPrincipal(): void
    {
        $principal = new ApiPrincipal();
        $repository = self::createMock(ApiPrincipalRepository::class);
        $repository->expects(self::once())
            ->method('findByToken')
            ->with('s3cr3t')
            ->willReturn($principal);

        $passport = new ApiTokenAuthenticator($repository)
            ->authenticate($this->request('Bearer s3cr3t'));

        self::assertInstanceOf(SelfValidatingPassport::class, $passport);

        $user = $passport->getBadge(UserBadge::class)?->getUser();

        self::assertInstanceOf(ApiPrincipalUser::class, $user);
        self::assertSame($principal, $user->getApiPrincipal());
    }

    /**
     * The token is the whole credential, so a token nobody knows is where authentication ends.
     */
    public function testRefusesATokenThatIsNotKnown(): void
    {
        $this->expectException(BadCredentialsException::class);

        $this->authenticator()->authenticate($this->request('Bearer nonsense'));
    }

    /**
     * `supports()` keeps this from happening, and it is an error rather than an anonymous request if it does.
     */
    public function testRefusesARequestWithoutAToken(): void
    {
        $this->expectException(BadCredentialsException::class);

        $this->authenticator()->authenticate($this->request(null));
    }

    public function testMintsATokenOfItsOwnTypeSoTheVoterCanTellApiRequestsApart(): void
    {
        $user = new ApiPrincipalUser(new ApiPrincipal());
        $passport = new SelfValidatingPassport(
            new UserBadge(
                $user->getUserIdentifier(),
                static fn (): UserInterface => $user,
            ),
        );

        $token = $this->authenticator()->createToken($passport, 'api');

        self::assertInstanceOf(ApiToken::class, $token);
        self::assertSame($user, $token->getUser());
        self::assertSame([ApiPrincipalUser::ROLE], $token->getRoleNames());
    }

    public function testRefusesToAuthenticateAnythingThatIsNotAnApiPrincipal(): void
    {
        $user = self::createStub(UserInterface::class);
        $passport = new SelfValidatingPassport(
            new UserBadge(
                'someone',
                static fn (): UserInterface => $user,
            ),
        );

        $this->expectException(LogicException::class);

        $this->authenticator()->createToken($passport, 'api');
    }

    /**
     * The challenge is the same whether the request carried no token or one that could not be resolved, so neither
     * answer says anything about which tokens exist.
     */
    public function testChallengesWithoutSayingAnything(): void
    {
        $response = $this->authenticator()->start($this->request(null));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(ApiTokenAuthenticator::CHALLENGE, $response->headers->get('WWW-Authenticate'));
        self::assertEmpty($response->getContent());
    }

    private function authenticator(): ApiTokenAuthenticator
    {
        $repository = self::createStub(ApiPrincipalRepository::class);
        $repository->method('findByToken')->willReturn(null);

        return new ApiTokenAuthenticator($repository);
    }

    private function request(?string $authorization): Request
    {
        $request = Request::create('https://database.gewis.nl/api/health');

        if (null !== $authorization) {
            $request->headers->set('Authorization', $authorization);
        }

        return $request;
    }
}
