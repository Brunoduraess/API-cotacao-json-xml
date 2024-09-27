<?php

$dbHost = '10.10.0.239';
$dbUsername = 'sistemas';
$dbPassword = 'rmv*7653Gums';
$dbName = 'transportegene03';


$conexao = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

$createTable = "CREATE TABLE `api_cotacao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user` varchar(50) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `cnpjPagador` varchar(14) NOT NULL,
  `cpfCnpjDestinatario` varchar(14) NOT NULL,
  `cepOrigem` varchar(8) NOT NULL,
  `cepDestino` varchar(8) NOT NULL,
  `mercadoria` varchar(1) NOT NULL,
  `valorNF` double NOT NULL,
  `quantidade` integer NOT NULL,
  `peso` double NOT NULL,
  `volume` double  NOT NULL,
  `material` varchar(100) NULL, 
  `embalagem` varchar(100) NULL,
  `request` text NOT NULL,
  `response` text NOT NULL,
  `status` varchar(100) NOT NULL,
  `error_message` text DEFAULT NULL,
  `error_number` int(3) DEFAULT NULL,
  `frete` double NULL,
  `mediaKg` double NULL,
  `prazo` int(3) NULL,
  `dataPrazo` DATE NULL,
  `cotacao` varchar(8) NULL,
  `token` text NULL,
  `desistencia` text NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;";
