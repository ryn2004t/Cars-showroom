<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Security\JwtAuth;
use App\Support\RequestValidator;
use App\Support\ResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthController
{
    public function login(Request $request): Response
    {
        $input = json_decode($request->getContent(), true) ?? [];
        $errors = RequestValidator::validate($input, [
            'email' => 'required|email',
            'password' => 'required|string|min:8|max:128',
        ]);
        if ($errors) {
            return ResponseFactory::json(['message' => 'Invalid input', 'errors' => $errors], 422);
        }

        // TODO: Replace with real user lookup and password verify
        $email = strtolower(trim($input['email']));
        $isDemo = $email === 'demo@example.com' && $input['password'] === 'DemoPass123!';
        if (!$isDemo) {
            // Hide which field failed
            return ResponseFactory::json(['message' => 'Invalid credentials'], 401);
        }

        $jwt = new JwtAuth();
        $token = $jwt->issue(['sub' => 'user-1', 'role' => 'owner']);

        return ResponseFactory::json([
            'token' => $token,
            'userId' => 'user-1',
            'role' => 'owner',
        ]);
    }
}
