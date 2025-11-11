<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar login
verificarLogin();

// Verificar se o ID da escola foi fornecido
if (!isset($_GET['escola_id']) || empty($_GET['escola_id'])) {
    echo json_encode([]);
    exit;
}

$escola_id = intval($_GET['escola_id']);
$conn = conectarBD();

// Buscar turmas da escola
$sql = "SELECT id, nome FROM turmas WHERE escola_id = ? ORDER BY nome";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $escola_id);
$stmt->execute();
$result = $stmt->get_result();

$turmas = [];
while ($row = $result->fetch_assoc()) {
    $turmas[] = $row;
}

// Retornar como JSON
header('Content-Type: application/json');
echo json_encode($turmas);
?>