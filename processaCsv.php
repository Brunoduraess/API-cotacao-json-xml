<?php

include 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo_csv'])) {
    $arquivoCSV = $_FILES['arquivo_csv']['tmp_name'];

    if (($handle = fopen($arquivoCSV, "r")) !== FALSE) {
        $contagem = 0;
        while (($dados = fgetcsv($handle, 1000, ",")) !== FALSE) {
            foreach ($dados as $campo) {
                $separaDados = explode(';', $campo);
                $cnpj = $separaDados[0];
                $curva = $separaDados[1];

                if ($cnpj != "cnpj" and $curva != "curva") {
                    $buscaCnpj = "SELECT cnpj FROM tabela_curvas WHERE cnpj = $cnpj";
                    $queryCnpj = $conexao->query($buscaCnpj);

                    if (mysqli_num_rows($queryCnpj) > 0) {
                        $atualizaCurva = "UPDATE tabela_curvas SET curva = '$curva' WHERE cnpj = '$cnpj'";
                    } else {
                        $atualizaCurva = "INSERT INTO tabela_curvas(cnpj, curva) VALUES (
                        '" . $cnpj . "', 
                        '" . $curva . "')";
                    }

                    $executa = mysqli_query($conexao, $atualizaCurva);
                }

                $contagem++;
            }
        }

        fclose($handle);

        echo "<script>alert('Arquivo processado. Total de linhas processadas: " . $contagem . "')</script>";
    } else {
        echo "Erro ao abrir o arquivo.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload de CSV</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"
        integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

    <style>
        .container {
            margin-top: 5%;
            width: 40%;
            margin-left: 30%;
            border-radius: 10px;
            border: 0.5px solid #ccc;
            padding: 1%;
            box-shadow: #2E2E2E 1px;
            box-shadow: 0 0 15px -2px rgba(0, 0, 0, .7);
        }

        h2 {
            text-align: center;
            font-weight: 600;
            padding-bottom: 2%;
        }

        .formBody {
            padding-top: 2%;
            padding-bottom: 2%;
        }

        button {
            padding-top: 2%;
            width: 100%;
            background-color: #0a2d8b;
            color: #fff;

        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Carregar Arquivo CSV tabela curvas</h2>
        <div class="alert alert-primary" role="alert">
            <b>Atenção,</b> Para o bom funcionamento da leitura do arquivo, é obrigatório que o formato seja <b>.csv</b>
            e que o nome das colunas sejam <b>cnpj</b> e <b>curva</b> respectivamente.
        </div>
        <form action="processaCsv.php" method="post" enctype="multipart/form-data">
            <div class="formBody">
                <label for="arquivo_csv">Escolha o arquivo CSV:</label>
                <input type="file" name="arquivo_csv" id="arquivo_csv" accept=".csv" required>
            </div>

            <button class="btn btn-sucess" type="submit">Enviar</button>
        </form>
    </div>

</body>

</html>