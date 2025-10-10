<?php

declare(strict_types=1);

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;

final class JwtAuth
{
    private string $secret;
    private string $issuer;
    private int $ttlSeconds;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'dev-secret-change-me';
        $this->issuer = $_ENV['JWT_ISS'] ?? 'service-provider-app';
        $this->ttlSeconds = (int)($_ENV['JWT_TTL'] ?? 3600);
    }

    /**
     * @param array{sub?:string,role?:string} $claims
     */
    public function issue(array $claims): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => $this->issuer,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttlSeconds,
        ], $claims);

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * @return array{sub:string,role?:string}|null
     */
    public function authenticateFromRequest(Request $request): ?array
    {
        $header = $request->headers->get('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            // Basic checks
            if (!isset($decoded->sub)) {
                return null;
            }
            return [
                'sub' => (string)$decoded->sub,
                'role' => isset($decoded->role) ? (string)$decoded->role : null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
