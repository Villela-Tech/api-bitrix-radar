<?php

namespace App\Application\Controller;

use App\Application\Services\BitrixService;
use App\Application\Utils\Utils;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DealRadarController
{
    use Utils;

    public function registerDealBitrixInRadar(Request $request, Response $response, $args)
    {

        try {
            $bitrix = new BitrixService();

            $result = $bitrix->registerDealBitrixInRadar($args['id_deal']);

            $response->getBody()->write(json_encode($result));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $this->writeLogError($e);
            return $e;
        }
    }
}
