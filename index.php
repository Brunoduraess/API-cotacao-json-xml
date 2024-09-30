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

// Verifica se o JSON foi recebido corretamente
if (!$requestData) {
    // Retorna um erro se os dados JSON não forem recebidos corretamente
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
} else {
    include('function.php');
    
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

}

