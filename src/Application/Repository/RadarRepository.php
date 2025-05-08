<?php

namespace App\Application\Repository;

use App\Application\Handlers\Request;
use App\Application\Utils\Utils;
use Exception;

class RadarRepository
{
    use Utils;

    public function GravarCFRT($bodyCFRT, $id_deal)
    {
        // Log dos dados que serão enviados
        $this->writeLogError(new Exception("Dados completos do CFRT a serem enviados: " . json_encode([
            'endereco' => [
                'Cidade' => $bodyCFRT['EnderecoPadrao']['Cidade'],
                'UF' => $bodyCFRT['EnderecoPadrao']['UF'],
                'Bairro' => $bodyCFRT['EnderecoPadrao']['Bairro'],
                'Endereco' => $bodyCFRT['EnderecoPadrao']['Endereco'],
                'CEP' => $bodyCFRT['EnderecoPadrao']['CEP'],
                'DDDTelefone' => $bodyCFRT['EnderecoPadrao']['DDDTelefone'],
                'Telefone' => $bodyCFRT['EnderecoPadrao']['Telefone'],
                'Email' => $bodyCFRT['EnderecoPadrao']['Email']
            ],
            'cpf_cnpj' => $bodyCFRT['CPF_CNPJ'],
            'nome' => $bodyCFRT['Nome'],
            'categoria' => $bodyCFRT['Categoria1'],
            'representantes' => $bodyCFRT['Representantes'],
            'vendedores' => $bodyCFRT['Vendedores']
        ], JSON_PRETTY_PRINT)), false);

        $body = [
            'login' => [
                'Base' => $_ENV['BASE_RADAR'],
                'Usuario' => $_ENV['USER_RADAR'],
                'Senha' => $_ENV['PASSWORD_RADAR'],
            ],
            'cfrt' => $bodyCFRT
        ];

        $request = new Request();
        $url = $_ENV['URL_RADAR'] . 'Empresarial/Empresarial.svc/json/GravarCFRT';

        try {
            $response = $request->PostRequest($url, $body, $id_deal);
            
            // Log da resposta
            if ($response) {
                $this->writeLogError(new Exception("Resposta do Radar: " . json_encode($response, JSON_PRETTY_PRINT)), false);
            }
            
            return $response;
        } catch (Exception $e) {
            $this->writeLogError(new Exception("Erro ao chamar o Radar: " . $e->getMessage() . "\nURL: " . $url . "\nBody: " . json_encode($body, JSON_PRETTY_PRINT)), false);
            throw $e;
        }
    }

    public function GravarContratoGerenciador($bodyContratoGerenciador, $id_deal)
    {
        // Log dos dados que serão enviados
        $this->writeLogError(new Exception("Dados do ContratoGerenciador a serem enviados: " . json_encode([
            'cpf_cnpj' => $bodyContratoGerenciador['CPF_CNPJ_Cliente'],
            'codigo_filial' => $bodyContratoGerenciador['CodigoFilial'],
            'classificacao' => $bodyContratoGerenciador['Classificacao']
        ], JSON_PRETTY_PRINT)), false);

        $body = [
            'login' => [
                'Base' => $_ENV['BASE_RADAR'],
                'Usuario' => $_ENV['USER_RADAR'],
                'Senha' => $_ENV['PASSWORD_RADAR'],
            ],
            'contrato' => $bodyContratoGerenciador
        ];

        $request = new Request();

        $url = $_ENV['URL_RADAR'] . 'Gerenciador/Gerenciador.svc/json/GravarContratoGerenciador';

        $response = $request->PostRequest($url, $body, $id_deal);
        
        // Log da resposta
        if ($response) {
            $this->writeLogError(new Exception("Resposta do GravarContratoGerenciador: " . json_encode($response)), false);
        }
        
        return $response;
    }

    public function gravarCFRT(array $dados): array
    {
        // Log dos dados recebidos
        $this->logger->info('Dados do CFRT a serem enviados: ' . json_encode($dados, JSON_PRETTY_PRINT));

        // Garantir estrutura correta do endereço
        $endereco = [
            'Cidade' => mb_strtoupper($dados['endereco']['Cidade'] ?? '', 'UTF-8'),
            'UF' => $dados['endereco']['UF'],
            'Bairro' => mb_strtoupper($dados['endereco']['Bairro'] ?? '', 'UTF-8'),
            'Endereco' => mb_strtoupper($dados['endereco']['Endereco'] ?? '', 'UTF-8'),
            'CEP' => preg_replace('/[^0-9]/', '', $dados['endereco']['CEP'] ?? ''),
            'DDDTelefone' => $dados['endereco']['DDDTelefone'] ?? '',
            'Telefone' => $dados['endereco']['Telefone'] ?? '',
            'Email' => $dados['endereco']['Email'] ?? ''
        ];

        $payload = [
            'endereco' => $endereco,
            'cpf_cnpj' => $dados['cpf_cnpj'],
            'nome' => $dados['nome'],
            'categoria' => $dados['categoria'] ?? '0',
            'representantes' => $dados['representantes'] ?? [['Codigo' => null]],
            'vendedores' => $dados['vendedores'] ?? [['Codigo' => '19703']]
        ];

        // Log do payload final
        $this->logger->info('Dados completos do CFRT a serem enviados: ' . json_encode($payload, JSON_PRETTY_PRINT));

        return $this->post('/GravarCFRT', $payload);
    }
}
