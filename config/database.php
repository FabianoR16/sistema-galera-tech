<?php
// Configurações do banco de dados - precisa alterar dados do BD para o ambiente de produção (publicação online)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'galera_tech_sistema');

// Conexão com o banco de dados
function conectarBD()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    // Verifica a conexão
    if ($conn->connect_error) {
        die("Falha na conexão: " . $conn->connect_error);
    }
    // Define charset para utf8 - codificação para caracteres especiais
    $conn->set_charset("utf8");
    return $conn;
}
