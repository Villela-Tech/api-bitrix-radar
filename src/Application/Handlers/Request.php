<?php

namespace App\Application\Handlers;

use App\Application\Repository\BitrixRepository;
use App\Application\Utils\Utils;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Request
{

    use Utils;

    public function GetRequest(string $url, array $header = [])
    {
        $client = new Client();
        try {
            usleep(500000);
            $response = $client->request("GET", $url, [
                'headers' => $header
            ]);

            if ($response->getStatusCode() == 200) {
                $arrayResponse = $response->getBody();
                return json_decode($arrayResponse, true);
            }
        } catch (RequestException $e) {
            $this->writeLogError($e);
        }
    }

    public function PostRequest(string $url, array $body = [], $id_deal = 0)
    {
        $client = new Client();
        $bitrixRepository = new BitrixRepository();

        try {
            $res = $client->request("POST", $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest'
                ],
                'json' => $body
            ]);

            if ($res->getStatusCode() == 200) {
                $arrayResponse = $res->getBody();
                return json_decode($arrayResponse, true);
            }
        } catch (RequestException $e) {
            $this->writeLogError($e);
            if ($id_deal != 0) {
                if ($e->hasResponse()) {
                    $response = $e->getResponse();
                    $body = $response->getBody();
                    throw $bitrixRepository->messageBitrix($id_deal, $body, true);
                }
            }
        }
    }
}
