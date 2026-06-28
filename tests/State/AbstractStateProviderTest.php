<?php

namespace App\Tests\State;

use App\Enum\CodeError;
use App\State\AbstractStateProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for AbstractStateProvider — pure unit tests, no kernel needed.
 */
class AbstractStateProviderTest extends TestCase
{
    private AbstractStateProvider $provider;

    protected function setUp(): void
    {
        // Create a concrete anonymous subclass to test the abstract class
        $this->provider = new class extends AbstractStateProvider {
            public function callOk(mixed $message = null): array
            {
                return $this->OK($message);
            }

            public function callError(CodeError $code, string $message, int $httpCode = Response::HTTP_BAD_REQUEST): array
            {
                return $this->error($code, $message, $httpCode);
            }
        };
    }

    // ───────────────────────── OK ─────────────────────────

    public function testOkWithNullReturnsMinimalResponse(): void
    {
        $result = $this->provider->callOk(null);

        $this->assertEquals(Response::HTTP_OK, $result['code']);
        $this->assertEquals('OK', $result['status']);
        $this->assertArrayNotHasKey('message', $result);
    }

    public function testOkWithNoArgReturnsMinimalResponse(): void
    {
        $result = $this->provider->callOk();

        $this->assertEquals(Response::HTTP_OK, $result['code']);
        $this->assertEquals('OK', $result['status']);
        $this->assertArrayNotHasKey('message', $result);
    }

    public function testOkWithMessageIncludesMessage(): void
    {
        $message = ['key' => 'value', 'count' => 42];
        $result  = $this->provider->callOk($message);

        $this->assertEquals(Response::HTTP_OK, $result['code']);
        $this->assertEquals('OK', $result['status']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals($message, $result['message']);
    }

    public function testOkWithStringMessage(): void
    {
        $result = $this->provider->callOk('hello');

        $this->assertEquals('hello', $result['message']);
        $this->assertEquals('OK', $result['status']);
    }

    public function testOkWithArrayDataIsPreserved(): void
    {
        $data = ['action' => 'created', 'votes' => ['👍' => 5], 'userVotes' => ['👍']];
        $result = $this->provider->callOk($data);

        $this->assertEquals($data, $result['message']);
    }

    // ───────────────────────── error ─────────────────────────

    public function testErrorReturnsCorrectStructure(): void
    {
        $result = $this->provider->callError(
            CodeError::USER_NOT_FOUND,
            'User not found',
            Response::HTTP_NOT_FOUND
        );

        $this->assertEquals(CodeError::USER_NOT_FOUND, $result['errorCode']);
        $this->assertEquals('User not found', $result['errorMessage']);
        $this->assertEquals('KO', $result['status']);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $result['code']);
    }

    public function testErrorDefaultsTo400(): void
    {
        $result = $this->provider->callError(CodeError::INVALID_DATA, 'Bad input');

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code']);
    }

    public function testErrorWithUnauthorizedCode(): void
    {
        $result = $this->provider->callError(
            CodeError::USER_NOT_AUTHENTICATED,
            'Not authenticated',
            Response::HTTP_UNAUTHORIZED
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $result['code']);
        $this->assertEquals(CodeError::USER_NOT_AUTHENTICATED, $result['errorCode']);
    }

    /**
     * @dataProvider errorCodeProvider
     */
    public function testAllErrorCodesCanBeReturned(CodeError $code, string $message): void
    {
        $result = $this->provider->callError($code, $message);

        $this->assertEquals($code, $result['errorCode']);
        $this->assertEquals($message, $result['errorMessage']);
        $this->assertEquals('KO', $result['status']);
    }

    public static function errorCodeProvider(): array
    {
        return [
            [CodeError::LOGIN_MISSING, 'No username'],
            [CodeError::PASSWORD_MISSING, 'No password'],
            [CodeError::EMAIL_MISSING, 'No email'],
            [CodeError::INVALID_TOKEN, 'Invalid token'],
            [CodeError::CLASSEMENT_NOT_FOUND, 'Classement not found'],
            [CodeError::CLASSEMENT_PASSWORD_REQUIRED, 'Password required'],
            [CodeError::PREFERENCES_NOT_FOUND, 'Preferences not found'],
            [CodeError::INVALID_DATA, 'Invalid data'],
        ];
    }
}
