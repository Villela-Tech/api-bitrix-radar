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

            // Log do deal antes de adicionar os produtos
            $this->writeLogError(new Exception("Deal antes de adicionar produtos: " . json_encode([
                'deal_id' => $deal['ID'],
                'tem_produtos' => isset($deal['Products']) || isset($deal['PRODUCTS']),
                'produtos_existentes' => $deal['Products'] ?? $deal['PRODUCTS'] ?? []
            ], JSON_PRETTY_PRINT)), false);

            // Adiciona os produtos ao deal
            if (!empty($entities['Products'])) {
                $deal['Products'] = $entities['Products'];
                $deal['PRODUCTS'] = $entities['Products'];
                
                // Log dos produtos adicionados
                $this->writeLogError(new Exception("Produtos adicionados ao deal: " . json_encode([
                    'deal_id' => $deal['ID'],
                    'produtos' => $entities['Products']
                ], JSON_PRETTY_PRINT)), false);
            } else {
                $this->writeLogError(new Exception("Nenhum produto encontrado para o deal " . $deal['ID']), false);
            }

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
