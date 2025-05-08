<?php

namespace App\Application\Entities;

use App\Application\Repository\BitrixRepository;
use App\Application\Utils\Utils;
use DateTime;
use Exception;
use Slim\Exception\HttpBadRequestException;

date_default_timezone_set('America/Sao_Paulo');

class ContratoGerenciador
{
    use Utils;

    public $bodyContratoGerenciador;

    public function __construct($deal, $company, $fields, $franqueado)
    {
        // Log inicial dos dados recebidos
        $this->writeLogError(new Exception("Dados recebidos no construtor: " . json_encode([
            'deal_id' => $deal['ID'],
            'tem_products' => isset($deal['Products']),
            'tem_PRODUCTS' => isset($deal['PRODUCTS']),
            'produtos_raw' => $deal['Products'] ?? $deal['PRODUCTS'] ?? []
        ], JSON_PRETTY_PRINT)), false);

        // Garantir que os produtos do catálogo estejam disponíveis
        if (isset($deal['Products']) && !empty($deal['Products'])) {
            $deal['PRODUCTS'] = $deal['Products'];
            
            // Log dos produtos recebidos
            $this->writeLogError(new Exception("Produtos recebidos no construtor: " . json_encode([
                'Products' => $deal['Products'],
                'PRODUCTS' => $deal['PRODUCTS']
            ], JSON_PRETTY_PRINT)), false);
        }
        $this->handlerBodyContratoGerenciador($deal, $company, $fields, $franqueado);
    }

    /**
     * @throws Exception
     */
    private function handlerBodyContratoGerenciador($deal, $company, $fields, $franqueado)
    {
        // Log inicial dos dados do deal
        $this->writeLogError(new Exception("Dados iniciais do deal: " . json_encode([
            'deal_id' => $deal['ID'],
            'tem_products' => isset($deal['Products']),
            'tem_PRODUCTS' => isset($deal['PRODUCTS']),
            'produtos_disponiveis' => $deal['Products'] ?? $deal['PRODUCTS'] ?? []
        ], JSON_PRETTY_PRINT)), false);

        $lawyerCornerCode = $this->searchLawyerCellCode($deal, $fields, 1);
        if (!$lawyerCornerCode) {
            $this->isRadarCodeFilled($deal['ID'], 'Célula');
        }

        $lawyerCellCode = $this->searchLawyerCode($deal, $fields, 1);
        if (!$lawyerCornerCode) {
            $this->isRadarCodeFilled($deal['ID'], 'Célula');
        }

        // Log detalhado para debug do produto
        $this->writeLogError(new Exception("Detalhes do produto no deal: " . json_encode([
            'deal_id' => $deal['ID'],
            'tem_produtos_catalogo' => isset($deal['PRODUCTS']) && !empty($deal['PRODUCTS']),
            'produtos_catalogo_raw' => isset($deal['PRODUCTS']) ? json_encode($deal['PRODUCTS']) : 'não definido',
            'produtos_catalogo_tipo' => gettype($deal['PRODUCTS']),
            'tem_produto_antigo' => isset($deal['UF_CRM_1745521763']) && !empty($deal['UF_CRM_1745521763']),
            'produto_antigo' => $deal['UF_CRM_1745521763'] ?? [],
            'campos_deal' => array_keys($deal)
        ], JSON_PRETTY_PRINT)), false);

        // Verifica se existe algum produto definido
        if ((!isset($deal['PRODUCTS']) || empty($deal['PRODUCTS'])) && 
            (!isset($deal['UF_CRM_1745521763']) || empty($deal['UF_CRM_1745521763']))) {
            $message = "Nenhum produto foi selecionado no negócio. É necessário selecionar um produto do catálogo.";
            $this->writeLogError(new Exception($message), false);
            $this->isRadarCodeFilled($deal['ID'], 'Produto', $message);
        }

        // Verifica primeiro o produto do catálogo
        $productRadarCode = null;
        
        // Log do estado atual dos produtos
        $this->writeLogError(new Exception("Valor do produto do catálogo: " . json_encode([
            'deal_id' => $deal['ID'],
            'produto_catalogo' => $deal['PRODUCTS'] ?? null,
            'produto_field' => $deal['UF_CRM_1745521763'] ?? null
        ])), false);

        if (isset($deal['PRODUCTS']) && !empty($deal['PRODUCTS'])) {
            foreach ($deal['PRODUCTS'] as $product) {
                if (!empty($product['PRODUCT_NAME'])) {
                    // Log do produto sendo processado
                    $this->writeLogError(new Exception("Processando produto do catálogo: " . json_encode([
                        'nome_completo' => $product['PRODUCT_NAME'],
                        'produto_completo' => $product
                    ], JSON_PRETTY_PRINT)), false);
                    
                    // Exemplo: "GOLDEN - Laudo Revisão Capacidade - 887115"
                    $parts = explode("-", $product['PRODUCT_NAME']);
                    $codigoRadar = trim(end($parts));
                    
                    // Log da extração do código
                    $this->writeLogError(new Exception("Extração do código do Radar: " . json_encode([
                        'partes' => $parts,
                        'ultima_parte' => $codigoRadar,
                        'match_encontrado' => preg_match('/\d+/', $codigoRadar, $matches) ? $matches[0] : 'não encontrado'
                    ], JSON_PRETTY_PRINT)), false);
                    
                    if (preg_match('/\d+/', $codigoRadar, $matches)) {
                        $productRadarCode = $matches[0];
                        $this->writeLogError(new Exception("Código do Radar encontrado no produto do catálogo: " . $productRadarCode), false);
                        break;
                    }
                }
            }

            if (!$productRadarCode) {
                $this->writeLogError(new Exception("Produto do catálogo não contém código do Radar no formato esperado: " . json_encode($deal['PRODUCTS'], JSON_PRETTY_PRINT)), false);
            }
        } else {
            $this->writeLogError(new Exception("Nenhum produto do catálogo encontrado no deal"), false);
        }

        // Se não encontrou no catálogo, tenta o formato antigo
        if (!$productRadarCode && isset($deal['UF_CRM_1745521763']) && !empty($deal['UF_CRM_1745521763'])) {
            $this->writeLogError(new Exception("Tentando buscar código no formato antigo: " . json_encode($deal['UF_CRM_1745521763'])), false);
            $productRadarCode = $this->searchInEnumerations($deal['UF_CRM_1745521763'][0], $fields['UF_CRM_1745521763']);
            if (!$productRadarCode) {
                $this->writeLogError(new Exception("Produto no formato antigo não contém código do Radar"), false);
            }
        }

        if (!$productRadarCode) {
            $message = "O produto selecionado não contém o código do Radar no formato esperado (deve terminar com um número após o último hífen)";
            $this->writeLogError(new Exception($message), false);
            $this->isRadarCodeFilled($deal['ID'], 'Produto', $message);
        }

        $listRateios = $this->listRateios(
            $deal['OPPORTUNITY'],
            $lawyerCornerCode,
            $lawyerCellCode,
            $productRadarCode
        );

        $finalDate = $this->dateNowAndAddDays($deal['DATE_CREATE'], 12);
        $data = new DateTime($deal['UF_CRM_1746140206']);

        $this->bodyContratoGerenciador = [
            'CPF_CNPJ_Cliente' => $deal['UF_CRM_1745494235'],
            'CodigoDocumento' => '004',
            'NumeroContrato' => $deal['UF_CRM_1561394928'],
            'CodigoFilial' => $lawyerCornerCode,
            'DataContratoInicial' => date("d/m/Y", strtotime($deal['UF_CRM_1746140492'])),
            'DataContratoFinal' => $finalDate['finalDate'],
            'DataFaturamento' => date("d/m/Y", strtotime($deal['UF_CRM_1746140492'])),
            'DataVencimento' => $data->format('d/m/Y'),
            'DescricaoContrato' => $this->searchProduct($deal, $fields, 0),
            'QuantidadeParcelas' => $deal['UF_CRM_1746140160'],
            'Rateios' => [$listRateios],
            'Nome' => $company['TITLE'],
            'RazaoSocialCliente' => $company['TITLE'],
            'RenovacaoAutomatica' => true,
            'Situacao' => 1,
            'Classificacao' => $productRadarCode,
            'TipoFaturamento' => 10,
            'UtilizaParcelamento' => false,
            'ValorOriginal' => str_replace(".", ",", $deal["OPPORTUNITY"]),
            'DadosInfoPlus' => [
                [
                    "Descricao" => "Tipo Contrato",
                    "Grupo" => "1",
                    "IdHeader" => 350,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 7,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Valor no Exito (%):",
                    "Grupo" => "",
                    "IdHeader" => 200,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 4,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Franqueado",
                    "Grupo" => "2",
                    "IdHeader" => 351,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 7,
                    "Valor" => $franqueado['UF_USR_1689948220200']
                ],
                [
                    "Descricao" => "Mês Venda",
                    "Grupo" => "",
                    "IdHeader" => 50,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 1,
                    "Valor" => date("m/Y", strtotime($deal['BEGINDATE']))
                ],
                [
                    "Descricao" => "Valor da Parcela",
                    "Grupo" => "",
                    "IdHeader" => 201,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 4,
                    "Valor" => str_replace(".", ",", $deal['UF_CRM_1746140177'])
                ],
                [
                    "Descricao" => "Qtde Parcelas",
                    "Grupo" => "",
                    "IdHeader" => 150,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 3,
                    "Valor" => (string)$deal['UF_CRM_1746140160']
                ],
                [
                    "Descricao" => "Motivo do Cancelamento",
                    "Grupo" => "3",
                    "IdHeader" => 353,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 7,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Observação Cancelamento",
                    "Grupo" => "",
                    "IdHeader" => 100,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 2,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Mês Protocolo(US)",
                    "Grupo" => "",
                    "IdHeader" => 51,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 1,
                    "Valor" => date("m/Y", strtotime($deal['BEGINDATE']))
                ],
                [
                    "Descricao" => "Unidade",
                    "Grupo" => "1",
                    "IdHeader" => 352,
                    "Obrigatorio" => true,
                    "TipoInfoPlus" => 7,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Êxito Limpa Nome",
                    "Grupo" => "",
                    "IdHeader" => 202,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 4,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Rate",
                    "Grupo" => "",
                    "IdHeader" => 203,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 4,
                    "Valor" => ""
                ]
            ]
        ];
    }

    public function getBodyContratoGerenciador()
    {
        return $this->bodyContratoGerenciador;
    }

    /**
     * @throws Exception
     */
    private function isRadarCodeFilled($dealId, $type, $message = null)
    {
        $bitrixRepository = new BitrixRepository();
        $bitrixRepository->messageBitrix(
            $dealId,
            json_encode(['Funcao' => 'GravarContratoGerenciador', 'Mensagem' => $message ?? "Não foi encontrado um valor de {$type} no deal ou o código do Radar não foi preechido no campo"]),
            true
        );
        throw new Exception($message ?? "Não foi encontrado um valor de {$type} no deal ou o código do Radar não foi preechido no campo", 400);
    }
}
