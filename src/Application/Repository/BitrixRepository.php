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
            ]
        ];

        $url = $_ENV['URL_BITRIX'] . $_ENV['TOKEN_BITRIX'] . $action;
        $result = $request->PostRequest($url, $body);

        // Log do resultado e tratamento de erros específicos
        if (isset($result['result']['result_error'])) {
            $errors = $result['result']['result_error'];
            $this->writeLogError(new Exception("Erros nas chamadas: " . json_encode($errors)), false);
            
            // Tratamento de erros específicos
            if (isset($errors['cmd0']) && $errors['cmd0']['error_description'] === "Not found") {
                throw new Exception("Empresa (ID: {$deal['COMPANY_ID']}) não encontrada no Bitrix", 500);
            }
            if (isset($errors['cmd1']) && $errors['cmd1']['error_description'] === "Not found") {
                throw new Exception("Contato (ID: {$deal['CONTACT_ID']}) não encontrado no Bitrix", 500);
            }
            if (isset($errors['cmd2']) && $errors['cmd2']['error_description'] === "Not found") {
                throw new Exception("Advogado Responsável (ID: {$deal['UF_CRM_1744295310']}) não encontrado no Bitrix", 500);
            }
            if (isset($errors['cmd4']) && $errors['cmd4']['error_description'] === "Not found") {
                throw new Exception("Franqueado (ID: {$deal['UF_CRM_1745432334']}) não encontrado no Bitrix", 500);
            }
        }

        if (count($result['result']['result_error']) == 0) {
            return [
                "Company" => $result['result']['result']['cmd0'],
                "Contact" => $result['result']['result']['cmd1'],
                "User" => $result['result']['result']['cmd2'][0],
                "Fields" => $result['result']['result']['cmd3'],
                "Franqueado" =>  $result['result']['result']['cmd4'][0]
            ];
        } else {
            $listErrors = [];
            foreach ($result['result']['result_error'] as $erro) {
                $this->writeLogError($erro, true);
                $listErrors[] = $erro['error'];
            }
            throw new Exception(json_encode($listErrors), 500);
        }
    }
}
