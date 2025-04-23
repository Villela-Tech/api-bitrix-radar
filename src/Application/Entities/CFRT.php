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
        $codeCategoriaRadar = $this->searchInEnumerations($deal['UF_CRM_5C4863CF06985'], $fields['UF_CRM_5C4863CF06985']);
        $lawyerRadarCode = $this->searchLawyerCode($deal, $fields, 1);

        $this->body = [
            "Categoria1" => $codeCategoriaRadar, // regiões
            "CPF_CNPJ" => $deal['UF_CRM_5C474435A75C9'],
            "Cliente" => true,
            "TipoPessoa" => 74,
            "Nome" => $company['UF_CRM_1638447301'],
            "RazaoSocial" => $company['UF_CRM_1638447301'],
            "Contato" => $contact['NAME'],
            "EnderecoPadrao" => [
                "CEP" => $company['UF_CRM_1638447403'],
                "DDDTelefone" => $deal['UF_CRM_1656337400'],
                "Telefone" => $deal['UF_CRM_1656337464'],
                "Email" => $company['EMAIL'][0]['VALUE'] ?? '',
                "Endereco" => $deal['UF_CRM_1646233040913'],
                "Cidade" => $deal['UF_CRM_1646233089039'],
                "NumeroEndereco" => $deal['UF_CRM_1646233049988'],
                "Bairro" => $deal['UF_CRM_1646233078922'],
                "UF" => $deal['UF_CRM_1646233101098'],
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
