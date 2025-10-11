<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Security\JwtAuth;
use App\Repositories\UserRepository;
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

        $email = strtolower(trim($input['email']));
        $repo = new UserRepository();
        $user = $repo->findByEmail($email);
        if (!$user || !password_verify($input['password'], $user['password_hash'])) {
            return ResponseFactory::json(['message' => 'Invalid credentials'], 401);
        }

        $jwt = new JwtAuth();
        $token = $jwt->issue(['sub' => (string)$user['id'], 'role' => (string)($user['role'] ?? 'staff')]);

        return ResponseFactory::json([
            'token' => $token,
            'userId' => (string)$user['id'],
            'role' => (string)($user['role'] ?? 'staff'),
        ]);
    }
}
