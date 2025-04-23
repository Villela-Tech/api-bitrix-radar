<?php

declare(strict_types=1);


use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));

$dotenv->load();

// Instantiate the app

$app = AppFactory::create();
// $app->setBasePath('/br24_vilella');
$app->setBasePath('/bitrix/integracao-radar-nbw'); // Produção

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

$app->run();

