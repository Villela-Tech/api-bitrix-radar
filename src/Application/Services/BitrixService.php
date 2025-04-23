<?php

namespace App\Application\Services;

use App\Application\Entities\CFRT;
use App\Application\Entities\ContratoGerenciador;
use App\Application\Repository\BitrixRepository;
use App\Application\Repository\RadarRepository;
use App\Application\Utils\Utils;
use Exception;

class BitrixService
{
    use Utils;

    public function registerDealBitrixInRadar(int $id_deal)
    {
        try {
            $bitrixRepository = new BitrixRepository();

            $deal = $bitrixRepository->getDealById($id_deal);
            $entities = $bitrixRepository->batchRequest($deal);

            $CRFT = new CFRT($deal, $entities);
            $bodyCRFT = $CRFT->getBodyCFRT();

            $ContratoGerenciador = new ContratoGerenciador(
                $deal,
                $entities['Company'],
                $entities['Fields'],
                $entities['Franqueado']
            );
            $bodyContratoGerenciador = $ContratoGerenciador->getBodyContratoGerenciador();

            $radarRepository = new RadarRepository();

            $responseCFRT = $radarRepository->GravarCFRT($bodyCRFT, $id_deal);
            $responseContratoGerenciador = $radarRepository
                ->GravarContratoGerenciador($bodyContratoGerenciador, $id_deal);

            if ($responseCFRT && $responseContratoGerenciador) {
                $bitrixRepository->messageBitrix($id_deal, "Integração Radar: Sucesso");
                return [$responseCFRT, $responseContratoGerenciador];
            }
        } catch (Exception $e) {
            $this->writeLogError($e);
            return $e;
        }
    }
}
