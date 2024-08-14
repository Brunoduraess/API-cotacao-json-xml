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
        $valorSemPonto = str_replace('.', '', $valor);

        // Substitui a vírgula decimal por um ponto
        $valorConvertido = str_replace(',', '.', $valorSemPonto);

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

        $valor = str_replace(',', '', $valor);

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

