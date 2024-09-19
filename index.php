<?php
// Define o horário local
date_default_timezone_set('America/Bahia');

// Bloquear o retorno de erros que não sejam da API
error_reporting(0);

// Defina o token de autorização esperado
$expectedToken = "Bearer e0c6fd31-b699-46ae-95cd-efebfcd78f55";

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

//Guarda no array para gravar no banco
$requestData['input'] = $jsonData;

include('function.php');

// Verifica se o JSON foi recebido corretamente
if (!$requestData) {
    // Retorna um erro se os dados JSON não forem recebidos corretamente
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

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

// Função para a ação de cotar
function cotar($requestData)
{
    global $wsdl, $serviceUrl;

    if ($requestData['cnpjPagador'] != "") {
        $cnpjPagador = limparCaracteres($requestData['cnpjPagador']);
    }
    if ($requestData['cnpjDestinatario'] != "") {
        $cnpjDestinatario = limparCaracteres($requestData['cnpjDestinatario']);
    }
    if ($requestData['cepOrigem'] != "") {
        $cepOrigem = limparCaracteres($requestData['cepOrigem']);
    }
    if ($requestData['cepDestino'] != "") {
        $cepDestino = limparCaracteres($requestData['cepDestino']);
    }

    if ($requestData['valorNF'] != "") {
        $valor = validarPontosEValores($requestData['valorNF']);
    }


    $peso = removeString($requestData['peso']);

    if ($peso == "") {
        $peso = "null";
    } else {
        $peso = validarPontosEValores($peso);
    }

    $quantidade = removeString($requestData['quantidade']);

    if ($quantidade == "") {
        $quantidade = "null";
    }

    $volume = round($requestData['volume'], 3);

    $telSemCP = substr($requestData['contact.number'], 2);

    $observacao = "COTACAO SACFLOW IA / WPP:" . $telSemCP . " / " . $volume . " / " . $requestData['material'] . " - " . $requestData['embalagem'];

    $soapRequest = <<<XML
<soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:sswinfbr.sswCotacao">
   <soapenv:Header/>
   <soapenv:Body>
      <urn:cotar soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
         <dominio xsi:type="xsd:string">{$requestData['dominio']}</dominio>
         <login xsi:type="xsd:string">{$requestData['login']}</login>
         <senha xsi:type="xsd:string">{$requestData['senha']}</senha>
         <cnpjPagador xsi:type="xsd:string">{$cnpjPagador}</cnpjPagador>
         <cepOrigem xsi:type="xsd:integer">{$cepOrigem}</cepOrigem>
         <cepDestino xsi:type="xsd:integer">{$cepDestino}</cepDestino>
         <valorNF xsi:type="xsd:decimal">{$valor}</valorNF>
         <quantidade xsi:type="xsd:integer">{$quantidade}</quantidade>
         <peso xsi:type="xsd:decimal">{$peso}</peso>
         <volume xsi:type="xsd:decimal">{$volume}</volume>
         <mercadoria xsi:type="xsd:integer">{$requestData['mercadoria']}</mercadoria>
         <ciffob xsi:type="xsd:string">{$requestData['ciffob']}</ciffob>
         <cnpjRemetente xsi:type="xsd:string">{$requestData['cnpj_remetente']}</cnpjRemetente>
         <cnpjDestinatario xsi:type="xsd:string">{$cnpjDestinatario}</cnpjDestinatario>
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

    // Chama a função e retorna o resultado
    $responseJson = callSoapApi($wsdl, $serviceUrl, $soapRequest);

    // include('register.php');

    $responseData = json_decode($responseJson, true);

    // Caso a cotação tenha sido executada com sucesso na API
    if ($responseData['erro'] === "0") {
        $status = "OK";
        $responseData['erro'] == "0";
        $errorMessage = "";
        $errorNumber = "null";

        // Criar data prazo dd/mm/yyyy
        $prazo = $responseData['prazo'];
        $prazo = date('Y-m-d', strtotime('+' . $prazo . ' days'));
        $prazo = validaDiaUtil($prazo);
        $responseData['dataPrazo'] = date('d-m-Y', strtotime($prazo));
        $dataDb = "'" . date('Y-m-d', strtotime($responseData['dataPrazo'])) . "'";

        // Converter frete para inserir no BD
        $frete = validarPontosEValores($responseData['frete']);

        // Cálculo de média kilo
        if ($requestData['peso'] != "" && $responseData['frete'] != "") {
            $responseData['mediaKG'] = round($frete / $peso, 2);
        } else {
            $responseData['mediaKG'] = "null";
        }
    } else {
        $status = "ERRO";
        $responseData['erro'] == "1";
        $errorMessage = $responseData['mensagem'];
        $frete = "null";
        $dataDb = "null";
        $responseData['prazo'] = "null";
        $responseData['mediaKG'] = "null";
        $responseData['dataPrazo'] = "null";
        $responseData['cotacao'] = "";
        $responseData['token'] = "";

        switch ($responseData['mensagem']) {
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
            case "OK Entrega em &aacute;rea de risco (opc304).<br> Coleta em &aacute;rea de risco (opc 304).<br>";
                $errorNumber = 12;
                break;
            case "Cidade origem n&atilde;o atendida. Cota&ccedil;&atilde;o deve ser cadastrada em unidade que efetuar&aacute; a emiss&atilde;o do CTRC.<br>";
                $errorNumber = 13;
                break;
            default:
                $errorNumber = 0;
                break;

        }

        $responseData['errorNumber'] = $errorNumber;
    }

    // Reencoda o array de volta para JSON
    $responseJsonAtualizado = json_encode($responseData, JSON_PRETTY_PRINT);

    // Inserção no banco de dados
    include('conexao.php');

    // Verifica se a conexão foi estabelecida com sucesso
    if (!$conexao) {
        // Retorna um erro se a conexão com o banco de dados falhar
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Failed to connect to database']);
        exit;
    }

    $sql = "INSERT INTO api_cotacao(user, phone, cnpjPagador, cpfCnpjDestinatario, cepOrigem, cepDestino, mercadoria, valorNF, quantidade, peso, volume, material, embalagem, request, response, status, error_message, error_number, frete, mediaKg, prazo, dataPrazo, cotacao, token) VALUES (
        '" . $requestData['contact.name'] . "',
        '" . $requestData['contact.number'] . "',
        '" . $cnpjPagador . "',
        '" . $cnpjDestinatario . "',
        '" . $cepOrigem . "',
        '" . $cepDestino . "',
        '" . $requestData['mercadoria'] . "',
        '" . $valor . "',
        " . $quantidade . ",
        " . $peso . ",
        '" . $volume . "',
        '" . $requestData['material'] . "',
        '" . $requestData['embalagem'] . "',
        '" . $requestData['input'] . "',
        '" . $responseJsonAtualizado . "',
        '" . $status . "', 
        '" . $errorMessage . "', 
        " . $errorNumber . ",
        " . $frete . ",
        " . $responseData['mediaKG'] . ",
        " . $responseData['prazo'] . ",
        " . $dataDb . ",
        '" . $responseData['cotacao'] . "',
        '" . $responseData['token'] . "')";


    $executa = mysqli_query($conexao, $sql);

    if (!$executa) {
        $responseData['error_insert_db'] = $sql;         
    }

    // Reencoda o array de volta para JSON
    $responseJsonAtualizado = json_encode($responseData, JSON_PRETTY_PRINT);

    return $responseJsonAtualizado;

}

// Função para a ação de coletar
function retorno($requestData)
{
    $cotacao = $requestData['cotacao'];
    $motivo = $requestData['motivo'];

    include('conexao.php');

    $update = "UPDATE api_cotacao SET desistencia = '$motivo' WHERE cotacao = '$cotacao'";

    mysqli_query($conexao, $update);

    return json_encode(['message' => 'Motivo da desistencia registrado.']);
}

// Roteamento de ação
$action = $requestData['action'];

if ($action == 'cotar') {
    $responseJson = cotar($requestData);

    echo $responseJson;

} elseif ($action == 'retorno') {
    $responseJson = retorno($requestData);
    echo $responseJson;
} else {
    // Ação inválida ou não fornecida
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => $action]);
}
