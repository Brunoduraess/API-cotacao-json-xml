<?php

$responseData = json_decode($responseJson, true);

//Caso a cotação tenha sido executado com sucesso na API
if ($responseData['mensagem'] == "OK") {
    $status = "OK";
    $errorMessage = "";
    $errorNumber = "null";

    //Criar data prazo dd/mm/yyyy
    $prazo = $responseData['prazo'];
    $prazo = date('Y-m-d', strtotime('+' . $prazo . ' days'));
    $prazo = validaDiaUtil($prazo);
    $responseData['dataPrazo'] = date('d-m-Y', strtotime($prazo));
    $dataDb = "'" . date('Y-m-d', strtotime($responseData['dataPrazo'])) . "'";

    //converter frete para inserir no BD
    $frete = validarPontosEValores($responseData['frete']);

    //Calculo de media kilo
    if ($requestData['peso'] != "" and $responseData['frete'] != "") {
        $responseData['mediaKG'] = round($frete / $peso, 2);
    } else {
        $responseData['mediaKG'] = "null";
    }

    //Erro na cotação    
} else {
    $status = "ERRO";
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
        default:
            $errorNumber = 0;
            break;

    }

    $responseData['errorNumber'] = $errorNumber;


}


// Reencoda o array de volta para JSON
$responseJsonAtualizado = json_encode($responseData, JSON_PRETTY_PRINT);


include('conexao.php');

$grava_log = "INSERT INTO api_cotacao(user, phone, cnpjPagador, cpfCnpjDestinatario, cepOrigem, cepDestino, mercadoria, valorNF, quantidade, peso, volume, material, embalagem, request, response, status, error_message, error_number, frete, mediaKg, prazo, dataPrazo, cotacao, token) VALUES (
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
    '" . $jsonData . "',
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

mysqli_query($conexao, $grava_log);


echo $responseJsonAtualizado;

