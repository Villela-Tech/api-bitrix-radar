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
                return $item['VALUE'];
            }
        }
        return "";
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
        // Usa o código do produto extraído do campo UF_CRM_1748604707924
        $codigoContaContabil = $productRadarCode;
        
        // Log dos valores que serão usados
        $this->writeLogError(new \Exception(
            "Valores para rateio: " .
            "Produto: " . $codigoContaContabil . ", " .
            "Corner: " . $lawyerCornerCode . ", " .
            "Célula: " . $lawyerCellCode
        ));
        
        $listRateios = [
            'RateioContabil' => [
                'CodigoConta' => $codigoContaContabil,      // código do produto (857367)
                'CodigoDepartamento' => $codigoContaContabil,// código do produto (857367)
                'CodigoFilial' => $lawyerCornerCode,        // código do corner (15)
                'CodigoHistorico' => '',
                'CodigoRequisitante' => '',
                'Quantidade' => 1,
                'ValorRateio' => str_replace(".", ",", $valorTotal)
            ],
            'RateiosGerenciais' => [
                [
                    'CodigoDepartamento' => $lawyerCellCode, // código da célula (18448)
                    'CodigoFilial' => $lawyerCornerCode,     // código do corner (15)
                    'CodigoConta' => $lawyerCellCode,        // código da célula (18448)
                    'CodigoHistorico' => '',
                    'CodigoRequisitante' => '',
                    'Quantidade' => 1,
                    'ValorRateio' => str_replace(".", ",", $valorTotal)
                ]
            ]
        ];

        // Log do objeto completo que será enviado
        $this->writeLogError(new \Exception("Objeto rateios: " . json_encode($listRateios)));
        
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
        if (!empty($deal['UF_CRM_1746140099'])) {
            $celulaTexto = $this->searchInEnumerations($deal['UF_CRM_1746140099'], $fields['UF_CRM_1746140099']);
            // Extrai o código do Radar (18448) que vem antes do |
            if (preg_match('/- (\d+) \|/', $celulaTexto, $matches)) {
                return $matches[1]; // Retorna o código do Radar (18448)
            }
        }
        return "0";
    }

    public function searchLawyerCellCode($deal, $fields, $indexString): string
    {
        if (!empty($deal['UF_CRM_1746140099'])) {
            $celulaTexto = $this->searchInEnumerations($deal['UF_CRM_1746140099'], $fields['UF_CRM_1746140099']);
            
            // Log do texto completo da célula
            $this->writeLogError(new \Exception("Texto da célula: " . $celulaTexto));
            
            // Extrai o número após o | (ex: "... | 15" -> "15")
            if (preg_match('/\| (\d+)$/', $celulaTexto, $matches)) {
                return $matches[1];
            }
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

    public function extractProductName($bbcodeTable): string
    {
        if (empty($bbcodeTable)) {
            return "0";
        }
        
        // Remove as tags BBCode e pega apenas o conteúdo do produto
        preg_match('/\[td\](.*?)\[\/td\]/', $bbcodeTable, $matches);
        
        if (isset($matches[1])) {
            // Extrai apenas o código numérico após o hífen
            if (preg_match('/- (\d+)/', $matches[1], $codeMatches)) {
                return $codeMatches[1];
            }
            return trim($matches[1]);
        }
        
        return "0";
    }
}
