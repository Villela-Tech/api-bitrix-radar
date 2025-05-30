<?php

namespace App\Application\Utils;

date_default_timezone_set("America/Sao_Paulo");

trait Utils
{
    public function writeLogError($erro, $erroBatch = false): void
    {

        $dateNow = date("Y.m.d");
        if (!file_exists(getcwd() . "/logs")) {
            mkdir(getcwd() . "/logs", 0777, true);
        }
        $oldLogs = [];
        if (is_file(getcwd() . "/logs/log-$dateNow.json")) {
            $oldLogs = json_decode(file_get_contents(getcwd() . "/logs/log-$dateNow.json"));
        } else {
            fopen(getcwd() . "/logs/log-$dateNow.json", 'w+');
        }
        $logData = [
            'timestamp' => date('d-m-Y H:i:s'),
            'message' => $erroBatch ? $erro['error_description'] : $erro->getMessage(),
            'code' => $erroBatch ? 500 : $erro->getCode(),
            'file' => $erroBatch ? "" : $erro->getFile(),
            'line' => $erroBatch ? "" : $erro->getLine(),
        ];
        $oldLogs[] = $logData;
        file_put_contents(getcwd() . "/logs/log-$dateNow.json", '');

        file_put_contents(getcwd() . "/logs/log-$dateNow.json", json_encode($oldLogs) . "\n", FILE_APPEND);
    }

    public function searchInEnumerations($valorDesejado, $enumeration)
    {
        foreach ($enumeration['items'] as $item) {
            if ($item['ID'] == $valorDesejado) {
                return preg_replace('/[^0-9]/', '', $item['VALUE']);
            }
        }
    }

    public function decideBillingType($qttdParcelas): string
    {
        switch ($qttdParcelas) {
            case $qttdParcelas <= 6:
                return "9";
            case $qttdParcelas > 6:
                return "10";
            default:
                return "11";
        }
    }

    public function listRateios($valorTotal, $lawyerCornerCode, $lawyerCellCode, $productRadarCode): array
    {
        $listRateios = [
            'RateioContabil' => [
                'CodigoConta' => $productRadarCode,
                'CodigoDepartamento' => $productRadarCode,
                'CodigoFilial' => $lawyerCornerCode,
                'CodigoHistorico' => '',
                'CodigoRequisitante' => '',
                'Quantidade' => 1,
                'ValorRateio' => str_replace(".", ",", $valorTotal)
            ],
            'RateiosGerenciais' => [
                [
                    'CodigoDepartamento' => $lawyerCellCode,
                    'CodigoFilial' => $lawyerCornerCode,
                    'CodigoConta' => $lawyerCellCode,
                    'CodigoHistorico' => '',
                    'CodigoRequisitante' => '',
                    'Quantidade' => 1,
                    'ValorRateio' => str_replace(".", ",", $valorTotal)
                ]
            ]
        ];
        return $listRateios;
    }

    public function dateNowAndAddDays(string $dateCreate, int $numMonths = 0): array
    {
        $date = date("d-m-Y", strtotime($dateCreate));
        $date = date_create($date);
        date_add($date, date_interval_create_from_date_string("{$numMonths} months"));
        date_sub($date, date_interval_create_from_date_string("2 days"));

        $finalDate = date_format($date, "d/m/Y");

        return ["finalDate" => $finalDate];
    }

    public function searchLawyerCode($deal, $fields, $indexString): string
    {
        if (!empty($deal['UF_CRM_1737037252'])) {
            $index = array_search(
                $deal['UF_CRM_1737037252'],
                array_column($fields['UF_CRM_1737037252']['items'], 'ID')
            );

            if ($index !== false && isset($fields['UF_CRM_1737037252']['items'][$index]['VALUE'])) {
                // Pega a parte do valor após o primeiro '-' e antes do '|'
                // 9.3.01.01 - SÃO PAULO 1 - MARCELO ARAÚJO - 20095 | 15
                $value = $fields['UF_CRM_1737037252']['items'][$index]['VALUE'];
                $codePart = explode("-", $value)[3] ?? '';
                $code = explode("|", $codePart)[0] ?? '0';

                return trim($code);
            }
        }
        return "0";
    }

    public function searchLawyerCellCode($deal, $fields, $indexString): string
    {
        if ($deal['UF_CRM_1737037252'] != "") {
            $index = array_search(
                $deal['UF_CRM_1737037252'],
                array_column($fields['UF_CRM_1737037252']['items'], 'ID')
            );
            $code = trim(explode("-", $fields['UF_CRM_1737037252']['items'][$index]['VALUE'])[3]) ?? "0";
            return trim(explode("|", $code)[$indexString]) ?? "0";
        }
        return "0";
    }

    public function searchProduct(array $deal, array $fields, int $indexString): string
    {
        foreach ($deal['UF_CRM_1586431691'] as $dealValue) {
            // Procurar o valor dentro do array $fields['UF_CRM_1586431691']['items']
            $index = array_search($dealValue, array_column($fields['UF_CRM_1586431691']['items'], 'ID'));
            // Se o índice for encontrado, retornar o valor correspondente
            if ($index !== false) {
                return trim(explode("-", $fields['UF_CRM_1586431691']['items'][$index]['VALUE'])[$indexString]) ?? "0";
            }
        }
        return "0";
    }
}
