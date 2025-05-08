<?php

namespace App\Application\Entities;

use App\Application\Utils\Utils;
use Exception;

class CFRT
{
    use Utils;

    public $body;

    public function __construct($deal, $entities)
    {
        $this->handleBodyCFRT($deal, $entities["Company"], $entities["Contact"], $entities["Fields"], $entities["User"], $entities["Franqueado"]);
    }


    private function handleBodyCFRT($deal, $company, $contact, $fields, $user, $franqueado)
    {
        $codeCategoriaRadar = $this->searchInEnumerations($deal['UF_CRM_5C4863CF06985'], $fields['UF_CRM_5C4863CF06985']);
        $lawyerRadarCode = $this->searchLawyerCode($deal, $fields, 1);

        // Validação do estado e município
        if (!isset($deal['UF_CRM_1746146438']) || empty($deal['UF_CRM_1746146438'])) {
            throw new Exception("Estado (UF) não informado no negócio", 400);
        }

        if (!isset($deal['UF_CRM_1746146397']) || empty($deal['UF_CRM_1746146397'])) {
            throw new Exception("Cidade não informada no negócio", 400);
        }

        // Log dos dados do estado para debug
        $this->writeLogError(new Exception("Dados do estado recebidos: " . json_encode([
            'id_estado' => $deal['UF_CRM_1746146438'],
            'campos_disponiveis' => array_keys($fields['UF_CRM_1746146438']),
            'items' => $fields['UF_CRM_1746146438']['items'] ?? 'não disponível'
        ], JSON_PRETTY_PRINT)), false);

        // Obter o código do estado
        $codigoEstado = 0;
        $siglaEstado = '';
        
        // Mapear a sigla do estado para o código numérico
        $mapaEstados = [
            'AC' => 1, 'AL' => 2, 'AP' => 3, 'AM' => 4, 'BA' => 5,
            'CE' => 6, 'DF' => 7, 'ES' => 8, 'GO' => 9, 'MA' => 10,
            'MT' => 11, 'MS' => 12, 'MG' => 13, 'PA' => 14, 'PB' => 15,
            'PR' => 16, 'PE' => 17, 'PI' => 18, 'RJ' => 19, 'RN' => 20,
            'RS' => 21, 'RO' => 22, 'RR' => 23, 'SC' => 24, 'SP' => 25,
            'SE' => 26, 'TO' => 27
        ];

        // Primeiro tentar obter do campo de texto
        if (isset($deal['UF_CRM_1746147266']) && !empty($deal['UF_CRM_1746147266'])) {
            $siglaEstado = trim(strtoupper($deal['UF_CRM_1746147266']));
            $codigoEstado = $mapaEstados[$siglaEstado] ?? 0;
        }

        // Se não encontrou no campo de texto, tentar pela enumeração
        if ($codigoEstado === 0 && isset($fields['UF_CRM_1746146438']['items'])) {
            foreach ($fields['UF_CRM_1746146438']['items'] as $item) {
                if ($item['ID'] == $deal['UF_CRM_1746146438']) {
                    $siglaEstado = trim($item['VALUE']);
                    $codigoEstado = $mapaEstados[$siglaEstado] ?? 0;
                    break;
                }
            }
        }

        // Log do resultado da conversão
        $this->writeLogError(new Exception("Resultado da conversão do estado: " . json_encode([
            'sigla_encontrada' => $siglaEstado,
            'codigo_calculado' => $codigoEstado,
            'id_original' => $deal['UF_CRM_1746146438']
        ], JSON_PRETTY_PRINT)), false);

        if ($codigoEstado === 0) {
            throw new Exception("Não foi possível determinar o código do estado a partir da UF selecionada: " . $siglaEstado, 400);
        }

        // Normalizar o nome do município
        $municipio = mb_strtoupper(trim($deal['UF_CRM_1746146397']), 'UTF-8');
        $municipio = preg_replace('/[áàãâä]/ui', 'A', $municipio);
        $municipio = preg_replace('/[éèêë]/ui', 'E', $municipio);
        $municipio = preg_replace('/[íìîï]/ui', 'I', $municipio);
        $municipio = preg_replace('/[óòõôö]/ui', 'O', $municipio);
        $municipio = preg_replace('/[úùûü]/ui', 'U', $municipio);
        $municipio = preg_replace('/[ç]/ui', 'C', $municipio);
        $municipio = preg_replace('/[^A-Z\s]/', '', $municipio);
        $municipio = trim(preg_replace('/\s+/', ' ', $municipio));
        
        // Remover artigos e preposições do início do nome
        $municipio = preg_replace('/^(O |A |OS |AS |DE |DO |DA |DOS |DAS |E )/i', '', $municipio);
        
        // Log para debug do município normalizado
        $this->writeLogError(new Exception("Município normalizado: " . $municipio), false);

        // Log dos dados de endereço para debug
        $this->writeLogError(new Exception("Dados de endereço a serem enviados: " . json_encode([
            'cidade' => $municipio,
            'estado_codigo' => $codigoEstado,
            'estado_sigla' => $siglaEstado,
            'estado_original' => $deal['UF_CRM_1746146438'],
            'bairro' => $deal['UF_CRM_1746146387'],
            'endereco' => $deal['UF_CRM_1746146377']
        ], JSON_PRETTY_PRINT)), false);

        $this->body = [
            "Categoria1" => $codeCategoriaRadar,
            "CPF_CNPJ" => $deal['UF_CRM_1745494235'],
            "Cliente" => true,
            "TipoPessoa" => 74,
            "Nome" => $company['TITLE'],
            "RazaoSocial" => $company['TITLE'],
            "Contato" => $contact['NAME'],
            "EnderecoPadrao" => [
                "CEP" => preg_replace('/[^0-9]/', '', $company['UF_CRM_1638447403']),
                "DDDTelefone" => $deal['UF_CRM_1746140439'],
                "Telefone" => $deal['UF_CRM_1746140456'],
                "Email" => $company['EMAIL'][0]['VALUE'] ?? '',
                "Endereco" => mb_strtoupper($deal['UF_CRM_1746146377'], 'UTF-8'),
                "Cidade" => $municipio,
                "NumeroEndereco" => $deal['UF_CRM_1646233049988'],
                "Bairro" => mb_strtoupper($deal['UF_CRM_1746146387'], 'UTF-8'),
                "UF" => $codigoEstado,
            ],
            "Representantes" => [
                [
                    "Codigo" => $franqueado['UF_XING']
                ]
            ],
            "Vendedores" => [
                [
                    "Codigo" => $lawyerRadarCode
                ]
            ],
            "DadosInfoPlus" => [
                [
                    "Descricao" => "Endereço no Bitrix",
                    "Grupo" => "",
                    "IdHeader" => 100,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 2,
                    "Valor" => ""
                ],
                [
                    "Descricao" => "Origem do Bitrix",
                    "Grupo" => "",
                    "IdHeader" => 450,
                    "Obrigatorio" => false,
                    "TipoInfoPlus" => 9,
                    "Valor" => "Sim"
                ]
            ],
        ];
    }

    public function getBodyCFRT()
    {
        return $this->body;
    }
}
