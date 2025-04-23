<?php

declare(strict_types=1);

// use App\Application\Controller\ClientController;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Api is Up!');
        return $response;
    });

    $app->post('/DealToRadar/{id_deal}', [new \App\Application\Controller\DealRadarController, 'registerDealBitrixInRadar']);
    $app->get('/teste', [new \App\Application\Controller\DealRadarController, 'teste']);
};
