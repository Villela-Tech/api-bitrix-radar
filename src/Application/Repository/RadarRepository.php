<?php

namespace App\Application\Repository;

use App\Application\Handlers\Request;

class RadarRepository
{

    public function GravarCFRT($bodyCFRT, $id_deal)
    {
        $body = [
            'login' => [
                'Base' => $_ENV['BASE_RADAR'],
                'Usuario' => $_ENV['USER_RADAR'],
                'Senha' => $_ENV['PASSWORD_RADAR'],
            ],
            'cfrt' => $bodyCFRT
        ];

        $request = new Request();

        $url = $_ENV['URL_RADAR'] . 'Empresarial/Empresarial.svc/json/GravarCFRT';

        $response = $request->PostRequest($url, $body, $id_deal);
        return $response;
    }

    public function GravarContratoGerenciador($bodyContratoGerenciador, $id_deal)
    {
        $body = [
            'login' => [
                'Base' => $_ENV['BASE_RADAR'],
                'Usuario' => $_ENV['USER_RADAR'],
                'Senha' => $_ENV['PASSWORD_RADAR'],
            ],
            'contrato' => $bodyContratoGerenciador
        ];

        $request = new Request();

        $url = $_ENV['URL_RADAR'] . 'Gerenciador/Gerenciador.svc/json/GravarContratoGerenciador';

        $response = $request->PostRequest($url, $body, $id_deal);
        return $response;
    }
}
