<?php
// URL do WSDL e do serviço SOAP
$wsdl = 'https://ssw.inf.br/ws/sswCotacaoColeta/index.php?wsdl';
$serviceUrl = 'https://ssw.inf.br/ws/sswCotacaoColeta/index.php';

// Captura os dados JSON enviados na requisição POST
$jsonData = file_get_contents('php://input');

$requestData = json_decode($jsonData, true);

include ('function.php');

$cnpj_pagador = limparCaracteres($requestData['cnpjPagador']);
$cnpj_remetente = limparCaracteres($requestData['cnpjRemetente']);
$cnpj_destinatario = limparCaracteres($requestData['cnpjDestinatario']);
$cep_origem = limparCaracteres($requestData['cepOrigem']);
$cep_destino = limparCaracteres($requestData['cepDestino']);
$valor = validarPontosEValores($requestData['valorNF']);

// $volume = $requestData['quantidade'] * $requestData['altura'] * $requestData['largura'] * $requestData['comprimento'];

$observacao = $requestData['material'] . " - " . $requestData['embalagem'];

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
         <volume xsi:type="xsd:decimal">{$requestData['volume']}</volume>
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
         <!-- <altura xsi:type="xsd:decimal">{$requestData['altura']}</altura>
         <largura xsi:type="xsd:decimal">{$requestData['largura']}</largura>
         <comprimento xsi:type="xsd:decimal">{$requestData['comprimento']}</comprimento> -->
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

    include ('conexao.php');

    if ($responseArray['mensagem'] == "OK"){
        $status = "OK";
        $errorMessage = "";
    } else {
        $status = "ERRO";
        $errorMessage = $responseArray['mensagem'];
    }

    $grava_log = "INSERT INTO api_cotacao(user, phone, request, response, status, error_message) VALUES (
        '" . $requestData['contact.name'] . "',
        '" . $requestData['contact.number'] . "',
        '" . $jsonData . "',
        '" . $responseJson . "',
        '" . $status . "', 
        '" . $errorMessage ."')";

    mysqli_query($conexao, $grava_log);

    echo $responseJson;

} else {
    // Retorna um erro se os dados JSON não forem recebidos corretamente
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid JSON input']);
}
?>