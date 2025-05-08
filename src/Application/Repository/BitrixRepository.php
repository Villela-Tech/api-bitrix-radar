<?php

namespace App\Application\Repository;

use App\Application\Handlers\Request;
use App\Application\Utils\Utils;
use Exception;

class BitrixRepository
{
    use Utils;

    public function getAllClients()
    {
        $request = new Request();

        $action = 'crm.company.list';

        $header = [
            "Content-Type" => "application/json"
        ];

        $url = $_ENV['URL_BITRIX'] . $_ENV['TOKEN_BITRIX'] . $action;

        $response = $request->GetRequest($url, $header);

        if ($response['result']) {
            return $response['result'];
        } else {
            return [];
        }
    }

    public function getDealById(int $id_deal)
    {
        $request = new Request();

        $action = "crm.deal.get?id={$id_deal}";

        $header = [
            "Content-Type" => "application/json"
        ];

        $url = $_ENV['URL_BITRIX'] . $_ENV['TOKEN_BITRIX'] . $action;

        $response = $request->GetRequest($url, $header);

        if ($response['result']) {
            return $response['result'];
        } else {
            return [];
        }
    }

    public function messageBitrix(int $id_deal, string $title, $error = false): void
    {

        $title = json_decode($title);

        $request = new Request();

        $action = 'crm.timeline.comment.add';

        $header = [
            "Content-Type" => "application/json"
        ];

        $params = [
            "fields" => [
                "ENTITY_ID" => $id_deal,

                "ENTITY_TYPE" => "Deal",

                "COMMENT" => $error ?
                    "Integração Radar: Erro. " . "\nFunção : {$title->Funcao} , \nMensagem : {$title->Mensagem}" :
                    "Integração Radar: Sucesso."
            ]
        ];
        $url = $_ENV['URL_BITRIX'] . $_ENV['TOKEN_BITRIX'] . $action . "?" . http_build_query($params);

        $request->GetRequest($url, $header);
    }

    public function batchRequest($deal): array
    {
        $request = new Request();
        $action = "batch";

        // Validação dos campos obrigatórios
        if (empty($deal['COMPANY_ID'])) {
            throw new Exception("Negócio não possui empresa vinculada", 500);
        }
        if (empty($deal['CONTACT_ID'])) {
            throw new Exception("Negócio não possui contato vinculado", 500);
        }
        if (empty($deal['UF_CRM_1744295310'])) {
            throw new Exception("Campo Advogado Responsável não preenchido", 500);
        }
        if (empty($deal['UF_CRM_1745432334'])) {
            throw new Exception("Campo Franqueado não preenchido", 500);
        }

        // Log dos valores que serão usados
        $this->writeLogError(new Exception(json_encode([
            'COMPANY_ID' => $deal['COMPANY_ID'],
            'CONTACT_ID' => $deal['CONTACT_ID'],
            'ADVOGADO' => $deal['UF_CRM_1744295310'],
            'FRANQUEADO' => $deal['UF_CRM_1745432334']
        ])), false);

        $body = [
            "cmd" => [
                "cmd0" => "crm.company.get?id={$deal['COMPANY_ID']}", // Company
                "cmd1" => "crm.contact.get?id={$deal['CONTACT_ID']}", // Contact
                "cmd2" => "user.get?ID={$deal['UF_CRM_1744295310']}", // Advogado Responsável
                "cmd3" => "crm.deal.fields", // fields
                "cmd4" => "user.get?ID={$deal['UF_CRM_1745432334']}", // Franqueado
                "cmd5" => "crm.deal.productrows.get?id={$deal['ID']}" // Produtos do negócio
            ]
        ];

        $url = $_ENV['URL_BITRIX'] . $_ENV['TOKEN_BITRIX'] . $action;
        $result = $request->PostRequest($url, $body);

        // Log do resultado da chamada batch
        $this->writeLogError(new Exception("Resultado da chamada batch: " . json_encode([
            'url' => $url,
            'body' => $body,
            'result' => $result
        ], JSON_PRETTY_PRINT)), false);

        // Verifica se houve erros nas chamadas
        $erros = [];
        if (isset($result['result']) && is_array($result['result'])) {
            foreach ($result['result'] as $key => $value) {
                if (isset($value['error'])) {
                    $erros[] = "Erro em {$key}: " . json_encode($value);
                }
            }
        }
        
        $this->writeLogError(new Exception("Erros nas chamadas: " . json_encode($erros)), false);

        if (!empty($erros)) {
            throw new Exception("Erros ao obter dados do Bitrix: " . implode(", ", $erros));
        }

        // Extrai os produtos do resultado
        $produtos = [];
        if (isset($result['result']['result']['cmd5'])) {
            $produtos = $result['result']['result']['cmd5'];
            
            // Log dos produtos encontrados
            $this->writeLogError(new Exception("Produtos encontrados na chamada batch: " . json_encode([
                'deal_id' => $deal['ID'],
                'produtos' => $produtos
            ], JSON_PRETTY_PRINT)), false);
        }

        return [
            'Company' => $result['result']['result']['cmd0'] ?? [],
            'Contact' => $result['result']['result']['cmd1'] ?? [],
            'User' => $result['result']['result']['cmd2'] ?? [],
            'Fields' => $result['result']['result']['cmd3'] ?? [],
            'Franqueado' => $result['result']['result']['cmd4'] ?? [],
            'Products' => $produtos
        ];
    }
}
