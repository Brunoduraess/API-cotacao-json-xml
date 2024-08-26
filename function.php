<?php

function limparCaracteres($valor)
{
    // Define os caracteres que você deseja remover
    $caracteresRemover = array('.', '-', '/');

    // Remove os caracteres da string
    $valorLimpo = str_replace($caracteresRemover, '', $valor);

    return $valorLimpo;
}

function validarPontosEValores($valor)
{

    $caracteresRemover = array('R', 'r', '$');

    // Remove os caracteres da string
    $valor = str_replace($caracteresRemover, '', $valor);


    $valor = str_replace(',', '.', $valor);

    // Contar quantos pontos existem na string
    $quantidadePontos = substr_count($valor, '.');

    // Verificar a parte da string após o último ponto
    $posUltimoPonto = strrpos($valor, '.');

    // Se houver um ponto na string, conta quantos caracteres vêm após ele
    if ($posUltimoPonto !== false) {
        $valoresAposPonto = strlen(substr($valor, $posUltimoPonto + 1));
    } else {
        $valoresAposPonto = 0;
    }


    if ($quantidadePontos == 1 and $valoresAposPonto > 2) {

        // $valor = str_replace(',', '', $valor);

        $valor = trim($valor);

        // Remove os pontos de milhar
        $valorConvertido = str_replace('.', '', $valor);

        // Certifica-se de que o valor é formatado com exatamente uma casa decimal
        // Primeiro, garante que o valor tenha exatamente uma casa decimal
        if (strpos($valorConvertido, '.') !== false) {
            $partes = explode('.', $valorConvertido);

            if (isset($partes[1])) {
                $parteInteira = $partes[0];
                $parteDecimal = substr($partes[1], 0, 1); // Mantém apenas uma casa decimal
            } else {
                $parteInteira = $partes[0];
                $parteDecimal = '0';
            }
        } else {
            $parteInteira = $valorConvertido;
            $parteDecimal = '0';
        }

        // Reconstrói o valor formatado
        $valorFormatado = $parteInteira . '.' . $parteDecimal;

        return $valorFormatado;
    } elseif ($quantidadePontos > 1) {

        // Encontrar a posição do último ponto
        $posUltimoPonto = strrpos($valor, '.');

        // Se houver pelo menos um ponto na string
        if ($posUltimoPonto !== false) {
            // Dividir a string em duas partes: antes e depois do último ponto
            $parteAntesDoUltimoPonto = substr($valor, 0, $posUltimoPonto);
            $parteDepoisDoUltimoPonto = substr($valor, $posUltimoPonto);

            // Remover todos os pontos da primeira parte
            $parteAntesDoUltimoPonto = str_replace('.', '', $parteAntesDoUltimoPonto);

            // Recombinar as duas partes
            $valor = $parteAntesDoUltimoPonto . $parteDepoisDoUltimoPonto;
        }


        return $valor;

    } else {
        return $valor;
    }
}

function validaDiaUtil($prazo)
{

    $hora = date("H", strtotime($prazo));

    if ($hora > 11) {
        $prazo = date('Y-m-d', strtotime('+1 days', strtotime($prazo)));
    }

    $dia = date("D", strtotime($prazo));
    $hora = date("H", strtotime($prazo));
    $ano = date("Y", strtotime($prazo));

    $feriados = ['' . $ano . '-01-01', '' . $ano . '-04-21', '' . $ano . '-05-01', '' . $ano . '-05-30', '' . $ano . '-09-07', '' . $ano . '-10-12', '' . $ano . '-11-02', '' . $ano . '-11-15', '' . $ano . '-11-20', '' . $ano . '-12-25'];

    //Valida se a data de entrada é feriado

    while ($dia == "Sat" || $dia == "Sun" || in_array($prazo, $feriados)) {
        // Adiciona um dia à data de coleta
        $prazo = date('Y-m-d', strtotime('+1 day', strtotime($prazo)));

        // Atualiza o dia da semana da nova data de coleta
        $dia = date("D", strtotime($prazo));
    }

    $prazo = date("Y-m-d", strtotime($prazo)) . "T";

    return $prazo;

}


