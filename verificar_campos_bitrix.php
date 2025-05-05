<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$campos_necessarios = [
    'DEAL' => [
        'COMPANY_ID' => 'ID da empresa vinculada',
        'CONTACT_ID' => 'ID do contato vinculado',
        'UF_CRM_1744295310' => 'ID do Advogado Responsável',
        'UF_CRM_1745432334' => 'ID do Franqueado',
        'UF_CRM_1745494235' => 'CPF/CNPJ do cliente',
        'UF_CRM_1745521763' => 'Código do Produto Radar',
        'UF_CRM_1746140099' => 'Código da Célula',
        'UF_CRM_1746140160' => 'Quantidade de Parcelas',
        'UF_CRM_1746140177' => 'Valor da Parcela',
        'UF_CRM_1746140206' => 'Data de Vencimento',
        'UF_CRM_1746140439' => 'DDD Telefone',
        'UF_CRM_1746140456' => 'Telefone',
        'UF_CRM_1746140492' => 'Data Inicial do Contrato',
        'UF_CRM_1746146377' => 'Endereço',
        'UF_CRM_1746146387' => 'Bairro',
        'UF_CRM_1746146397' => 'Cidade',
        'UF_CRM_1746146438' => 'UF (Estado)',
        'OPPORTUNITY' => 'Valor do Negócio',
        'BEGINDATE' => 'Data de Início',
        'DATE_CREATE' => 'Data de Criação'
    ],
    'COMPANY' => [
        'TITLE' => 'Nome/Razão Social',
        'UF_CRM_1638447403' => 'CEP',
        'EMAIL' => 'Email'
    ],
    'CONTACT' => [
        'NAME' => 'Nome do Contato'
    ],
    'USER' => [
        'UF_USR_1689948220200' => 'Código do Franqueado no Radar',
        'UF_XING' => 'Código do Representante'
    ]
];

function verificarCampos($url, $token) {
    $client = new GuzzleHttp\Client();
    $resultados = [];
    
    // Verificar campos do Deal
    try {
        $response = $client->request('GET', $url . $token . '/crm.deal.fields');
        $campos_deal = json_decode($response->getBody(), true);
        $resultados['DEAL'] = compararCampos($campos_deal['result'], $campos_necessarios['DEAL']);
    } catch (Exception $e) {
        $resultados['DEAL'] = ['erro' => $e->getMessage()];
    }

    // Verificar campos da Company
    try {
        $response = $client->request('GET', $url . $token . '/crm.company.fields');
        $campos_company = json_decode($response->getBody(), true);
        $resultados['COMPANY'] = compararCampos($campos_company['result'], $campos_necessarios['COMPANY']);
    } catch (Exception $e) {
        $resultados['COMPANY'] = ['erro' => $e->getMessage()];
    }

    // Verificar campos do Contact
    try {
        $response = $client->request('GET', $url . $token . '/crm.contact.fields');
        $campos_contact = json_decode($response->getBody(), true);
        $resultados['CONTACT'] = compararCampos($campos_contact['result'], $campos_necessarios['CONTACT']);
    } catch (Exception $e) {
        $resultados['CONTACT'] = ['erro' => $e->getMessage()];
    }

    // Verificar campos do User
    try {
        $response = $client->request('GET', $url . $token . '/user.fields');
        $campos_user = json_decode($response->getBody(), true);
        $resultados['USER'] = compararCampos($campos_user['result'], $campos_necessarios['USER']);
    } catch (Exception $e) {
        $resultados['USER'] = ['erro' => $e->getMessage()];
    }

    return $resultados;
}

function compararCampos($campos_existentes, $campos_necessarios) {
    $resultado = [
        'campos_encontrados' => [],
        'campos_faltantes' => []
    ];

    foreach ($campos_necessarios as $campo => $descricao) {
        if (isset($campos_existentes[$campo])) {
            $resultado['campos_encontrados'][$campo] = $descricao;
        } else {
            $resultado['campos_faltantes'][$campo] = $descricao;
        }
    }

    return $resultado;
}

// URL e token do Bitrix24
$url = $_ENV['URL_BITRIX'];
$token = $_ENV['TOKEN_BITRIX'];

// Executar a verificação
$resultados = verificarCampos($url, $token);

// Exibir resultados
echo "=== Resultado da Verificação ===\n\n";

foreach ($resultados as $entidade => $resultado) {
    echo "== $entidade ==\n";
    
    if (isset($resultado['erro'])) {
        echo "Erro ao verificar campos: " . $resultado['erro'] . "\n";
        continue;
    }

    echo "\nCampos encontrados:\n";
    foreach ($resultado['campos_encontrados'] as $campo => $descricao) {
        echo "✓ $campo ($descricao)\n";
    }

    echo "\nCampos faltantes:\n";
    foreach ($resultado['campos_faltantes'] as $campo => $descricao) {
        echo "✗ $campo ($descricao)\n";
    }
    
    echo "\n";
} 