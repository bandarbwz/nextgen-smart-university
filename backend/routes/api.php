<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Helpers\Router;

$router = new Router();
$auth = new AuthController();

$router->post('/api/v1/auth/login', fn () => $auth->login());
$router->post('/api/v1/auth/logout', fn () => $auth->logout());
$router->post('/api/v1/auth/refresh', fn () => $auth->refresh());

$router->post('/api/v1/auth/forgot-password', fn () => $auth->forgotPassword());
$router->post('/api/v1/auth/reset-password', fn () => $auth->resetPassword());
$router->put('/api/v1/auth/change-password', fn () => $auth->changePassword());

$router->post('/api/v1/auth/verify-email', fn () => $auth->verifyEmail());
$router->post('/api/v1/auth/resend-verification', fn () => $auth->resendVerification());

$router->get('/api/v1/auth/profile', fn () => $auth->profile());
$router->put('/api/v1/auth/profile', fn () => $auth->updateProfile());

$router->get('/api/v1/auth/sessions', fn () => $auth->sessions());
$router->delete('/api/v1/auth/sessions/{id}', fn (string $id) => $auth->revokeSession($id));

return $router;
