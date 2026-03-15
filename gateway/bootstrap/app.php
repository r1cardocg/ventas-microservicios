<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ← Este es el que faltaba — captura el error real
        $exceptions->render(function (RouteNotFoundException $e) {
            return response()->json(['error' => 'No autenticado'], 401);
        });

        $exceptions->render(function (TokenExpiredException $e) {
            return response()->json(['error' => 'Token expirado'], 401);
        });

        $exceptions->render(function (TokenInvalidException $e) {
            return response()->json(['error' => 'Token inválido'], 401);
        });

        $exceptions->render(function (JWTException $e) {
            return response()->json(['error' => 'Token no proporcionado'], 401);
        });

        $exceptions->render(function (AuthenticationException $e) {
            return response()->json(['error' => 'No autenticado'], 401);
        });

    })->create();
