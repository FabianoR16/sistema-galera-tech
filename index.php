<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar se o banco de dados está inicializado
function verificarBancoDados()
{
    $conn = conectarBD();
    $result = $conn->query("SHOW TABLES LIKE 'usuarios'");
    $tabela_existe = $result->num_rows > 0;
    $conn->close();
    return $tabela_existe;
}
// Se não estiver logado, redirecionar para login
if (!estaLogado()) {
    header("Location: login.php");
    exit;
}
// Se estiver logado, redirecionar para a página de presença (página principal)
header("Location: presenca.php");
exit;
