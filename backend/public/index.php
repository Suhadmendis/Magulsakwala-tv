<?php

require_once __DIR__ . '/../src/bootstrap.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$router = new Router();

AccountsController::routes($router);
YouTubeOAuthController::routes($router);
VideosController::routes($router);
ZodiacVideosController::routes($router);
ContentFeederController::routes($router);
ComposeController::routes($router);
FindingsController::routes($router);
CommentsController::routes($router);
ContentController::routes($router);
ThumbnailsController::routes($router);
ThumbnailPresetsController::routes($router);
AssetsController::routes($router);
AnalyticsController::routes($router);
SystemController::routes($router);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
