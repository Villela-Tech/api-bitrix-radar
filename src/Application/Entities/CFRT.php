<?php

namespace App\Application\Entities;

use App\Application\Utils\Utils;

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
        $codeCategoriaRadar = $this->searchInEnumerations($deal['UF_CRM_1744295310'], $fields['UF_CRM_1744295310']);
        $lawyerRadarCode = $this->searchLawyerCode($deal, $fields, 1);
        $franqueadoCode = $this->searchFranqueadoCode($deal, $fields);

        $this->body = [
            "Categoria1" => $codeCategoriaRadar, // regiões
            "CPF_CNPJ" => $deal['UF_CRM_1745494235'],
            "Cliente" => true,
            "TipoPessoa" => 74,
            "Nome" => $deal['TITLE'],
            "RazaoSocial" => $deal['TITLE'],
            "Contato" => $contact['NAME'],
            "EnderecoPadrao" => [
                "CEP" => $deal['UF_CRM_1748021587'],
                "DDDTelefone" => $deal['UF_CRM_1746140439'],
                "Telefone" => $deal['UF_CRM_1746140456'],
                "Email" => $company['EMAIL'][0]['VALUE'] ?? '',
                "Endereco" => $deal['UF_CRM_1746146377'],
                "Cidade" => $deal['UF_CRM_1746146397'],
                "NumeroEndereco" => $deal['UF_CRM_1746146377'],
                "Bairro" => $deal['UF_CRM_1746146387'],
                "UF" => $deal['UF_CRM_1748021665'],
            ],
            "Representantes" => [
                [
                    "Codigo" => $franqueadoCode
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
