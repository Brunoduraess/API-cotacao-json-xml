<?php
//Define o horário local
date_default_timezone_set('America/Bahia');

//bloquear o retorno de erros que não sejam da API
error_reporting(0);

// Defina o token de autorização esperado
$expectedToken = "";

// Captura o cabeçalho "Authorization"
$headers = apache_request_headers();
$authHeader = $headers['Authorization'] ?? '';

if ($authHeader !== $expectedToken) {
    // Token inválido ou não fornecido
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Token de autorização inválido']);
    exit;
}

// URL do WSDL e do serviço SOAP
$wsdl = 'https://ssw.inf.br/ws/sswCotacaoColeta/index.php?wsdl';
$serviceUrl = 'https://ssw.inf.br/ws/sswCotacaoColeta/index.php';

// Captura os dados JSON enviados na requisição POST
$jsonData = file_get_contents('php://input');

$requestData = json_decode($jsonData, true);

include('function.php');

$cnpj_pagador = limparCaracteres($requestData['cnpjPagador']);
$cnpj_destinatario = limparCaracteres($requestData['cnpjDestinatario']);
$cep_origem = limparCaracteres($requestData['cepOrigem']);
$cep_destino = limparCaracteres($requestData['cepDestino']);
$valor = validarPontosEValores($requestData['valorNF']);

$volume = round($requestData['volume'], 3);

$telSemCP = substr($requestData['contact.number'], 2);

$observacao = "COTACAO SACFLOW IA / WPP:" . $telSemCP . " / " . $volume . " / " . $requestData['material'] . " - " . $requestData['embalagem'];

// Verifica se o JSON foi recebido corretamente
if ($requestData) {
    // Preenche o XML com os dados do JSON
    $soapRequest = <<<XML
<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:sswinfbr.sswCotacao">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:cotar soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
         <dominio xsi:type="xsd:string">{$requestData['dominio']}</dominio>
         <login xsi:type="xsd:string">{$requestData['login']}</login>
         <senha xsi:type="xsd:string">{$requestData['senha']}</senha>
         <cnpjPagador xsi:type="xsd:string">{$cnpj_pagador}</cnpjPagador>
         <cepOrigem xsi:type="xsd:integer">{$cep_origem}</cepOrigem>
         <cepDestino xsi:type="xsd:integer">{$cep_destino}</cepDestino>
         <valorNF xsi:type="xsd:decimal">{$valor}</valorNF>
         <quantidade xsi:type="xsd:integer">{$requestData['quantidade']}</quantidade>
         <peso xsi:type="xsd:decimal">{$requestData['peso']}</peso>
         <volume xsi:type="xsd:decimal">{$volume}</volume>
         <mercadoria xsi:type="xsd:integer">{$requestData['mercadoria']}</mercadoria>
         <ciffob xsi:type="xsd:string">{$requestData['ciffob']}</ciffob>
         <cnpjRemetente xsi:type="xsd:string">{$cnpj_remetente}</cnpjRemetente>
         <cnpjDestinatario xsi:type="xsd:string">{$cnpj_destinatario}</cnpjDestinatario>
         <observacao xsi:type="xsd:string">{$observacao}</observacao>
         <trt xsi:type="xsd:string">{$requestData['trt']}</trt>
         <coletar xsi:type="xsd:string">{$requestData['coletar']}</coletar>
         <entDificil xsi:type="xsd:string">{$requestData['entDificil']}</entDificil>
         <destContribuinte xsi:type="xsd:string">{$requestData['destContribuinte']}</destContribuinte>
         <qtdePares xsi:type="xsd:integer">{$requestData['qtdePares']}</qtdePares>
         <fatorMultiplicador xsi:type="xsd:integer">{$requestData['fatorMultiplicador']}</fatorMultiplicador>
      </urn:cotar>
   </soapenv:Body>
</soapenv:Envelope>
XML;

    // Função para realizar a solicitação SOAP e retornar o resultado como JSON
    function callSoapApi($wsdl, $serviceUrl, $soapRequest)
    {
        try {
            // Configurações do cliente SOAP
            $client = new SoapClient($wsdl, [
                'trace' => 1, // Para depuração
                'exceptions' => true,
            ]);

            // Faz a solicitação SOAP personalizada
            $response = $client->__doRequest($soapRequest, $serviceUrl, 'cotar', SOAP_1_1);

            // Converte a resposta XML para um objeto SimpleXMLElement
            $xml = simplexml_load_string($response);

            // Extrai o XML contido dentro do campo <return>
            $returnContent = (string) $xml->xpath('//return')[0];

            // Converte o conteúdo de <return> em um objeto SimpleXMLElement
            $innerXml = simplexml_load_string($returnContent);

            // Converte o objeto SimpleXMLElement para JSON
            $json = json_encode($innerXml);

            return $json;
        } catch (SoapFault $fault) {
            return json_encode(['error' => $fault->getMessage()]);
        }
    }

    // Define os cabeçalhos para permitir o acesso CORS e especificar o tipo de conteúdo como JSON
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');

    // Chama a função e exibe o resultado
    $responseJson = callSoapApi($wsdl, $serviceUrl, $soapRequest);

    $responseArray = json_decode($responseJson, true);

    $prazo = $responseArray['prazo'];

    $prazo = date('Y-m-d', strtotime('+' . $prazo . ' days'));

    $prazo = validaDiaUtil($prazo);

    $responseArray['dataPrazo'] = date('d-m-Y', strtotime($prazo));

    if ($responseArray['mensagem'] == "OK") {
        $status = "OK";
        $errorMessage = "";
        $errorNumber = "null";
    } else {
        $status = "ERRO";
        $errorMessage = $responseArray['mensagem'];

        switch ($responseArray['mensagem']) {
            case "Para esta cidade de origem, por favor, entre em contato via whatsapp pelo telefone (24)99834-2599 para falar com nossa equipe":
                $errorNumber = 1;
                break;
            case "Cidade destino n&atilde;o atendida.":
                $errorNumber = 2;
                break;
            case "PESSOA FISICA NAO PODE SER CLIENTE PAGADOR":
                $errorNumber = 3;
                break;
            case "Informe peso e/ou cubagem.<br>":
                $errorNumber = 4;
                break;
            case "OK Entrega em &aacute;rea de risco (opc304).<br>":
                $errorNumber = 5;
                break;
            case "CNPJ do pagador deve ser diferente do CNPJ do destinat&aacute;rio para frete CIF.":
                $errorNumber = 6;
                break;
            case "Valor de nota fiscal inv&aacute;lida.<br>":
                $errorNumber = 7;
                break;
            case "OK Coleta em &aacute;rea de risco (opc 304).<br>":
                $errorNumber = 8;
                break;
            case "Volume (m3) deve ser no maximo 170 m3.":
                $errorNumber = 9;
                break;
            case "CNPJ/CPF INV&Aacute;LIDO":
                $errorNumber = 10;
                break;
            case "Cidade origem n&atilde;o encontrada":
                $errorNumber = 11;
                break;
            default:
                $errorNumber = 0;
                break;

        }

        $responseArray['errorNumber'] = $errorNumber;


    }


    // Reencoda o array de volta para JSON
    $responseJsonAtualizado = json_encode($responseArray, JSON_PRETTY_PRINT);


    include('conexao.php');

    $grava_log = "INSERT INTO api_cotacao(user, phone, request, response, status, error_message, error_number) VALUES (
        '" . $requestData['contact.name'] . "',
        '" . $requestData['contact.number'] . "',
        '" . $jsonData . "',
        '" . $responseJsonAtualizado . "',
        '" . $status . "', 
        '" . $errorMessage . "', 
        " . $errorNumber . ")";

    mysqli_query($conexao, $grava_log);


    echo $responseJsonAtualizado;

} else {
    // Retorna um erro se os dados JSON não forem recebidos corretamente
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid JSON input']);
}
