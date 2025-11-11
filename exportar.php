<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar login
verificarLogin();

$conn = conectarBD();
$tipo = $_GET['tipo'] ?? '';

// Exportar lista de alunos
if ($tipo == 'alunos') {
    $filtro_escola = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : 0;
    $filtro_turma = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
    $filtro_genero = isset($_GET['genero']) ? $_GET['genero'] : '';
    
    $query = "SELECT a.nome, a.user_genero as genero, a.data_nascimento, a.endereco, 
                    e.nome as escola, t.nome as turma, t.serie, t.turno,
                    a.nome_responsavel, a.tel_responsavel, a.email_responsavel
              FROM alunos a 
              LEFT JOIN turmas t ON a.turma_id = t.id 
              LEFT JOIN escolas e ON a.escola_id = e.id OR t.escola_id = e.id 
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($filtro_escola > 0) {
        $query .= " AND (a.escola_id = ? OR t.escola_id = ?)";
        $params[] = $filtro_escola;
        $params[] = $filtro_escola;
        $types .= "ii";
    }
    
    if ($filtro_turma > 0) {
        $query .= " AND a.turma_id = ?";
        $params[] = $filtro_turma;
        $types .= "i";
    }
    
    if (!empty($filtro_genero)) {
        $query .= " AND a.user_genero = ?";
        $params[] = $filtro_genero;
        $types .= "s";
    }
    
    $query .= " ORDER BY a.nome";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dados = [];
    while ($row = $result->fetch_assoc()) {
        // Formatar data de nascimento
        if (!empty($row['data_nascimento'])) {
            $row['data_nascimento'] = formatarData($row['data_nascimento']);
        }
        $dados[] = $row;
    }
    
    exportarParaExcel($dados, 'alunos_' . date('Y-m-d'));
}

// Exportar relatório de presença
if ($tipo == 'presenca') {
    $filtro_turma = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
    $filtro_aluno = isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : 0;
    $filtro_data_inicio = isset($_GET['data_inicio']) ? formatarDataBanco($_GET['data_inicio']) : '';
    $filtro_data_fim = isset($_GET['data_fim']) ? formatarDataBanco($_GET['data_fim']) : '';
    
    $query = "SELECT a.nome as aluno, a.user_genero as genero, 
                    t.nome as turma, t.serie, e.nome as escola,
                    au.data, au.tema, p.status, p.justificativa,
                    u.nome as registrado_por
              FROM presencas p
              JOIN alunos a ON p.aluno_id = a.id
              JOIN aulas au ON p.aula_id = au.id
              JOIN turmas t ON au.turma_id = t.id
              JOIN escolas e ON t.escola_id = e.id
              JOIN usuarios u ON p.usuario_id = u.id
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($filtro_turma > 0) {
        $query .= " AND au.turma_id = ?";
        $params[] = $filtro_turma;
        $types .= "i";
    }
    
    if ($filtro_aluno > 0) {
        $query .= " AND p.aluno_id = ?";
        $params[] = $filtro_aluno;
        $types .= "i";
    }
    
    if (!empty($filtro_data_inicio)) {
        $query .= " AND au.data >= ?";
        $params[] = $filtro_data_inicio;
        $types .= "s";
    }
    
    if (!empty($filtro_data_fim)) {
        $query .= " AND au.data <= ?";
        $params[] = $filtro_data_fim;
        $types .= "s";
    }
    
    $query .= " ORDER BY au.data DESC, a.nome";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dados = [];
    while ($row = $result->fetch_assoc()) {
        // Formatar data da aula
        if (!empty($row['data'])) {
            $row['data'] = formatarData($row['data']);
        }
        $dados[] = $row;
    }
    
    exportarParaExcel($dados, 'presenca_' . date('Y-m-d'));
}

// Exportar relatório de advertências
if ($tipo == 'advertencias') {
    $filtro_escola = isset($_GET['escola_id']) ? (int)$_GET['escola_id'] : 0;
    $filtro_turma = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
    $filtro_aluno = isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : 0;
    $filtro_tipo = isset($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : 0;
    
    $query = "SELECT a.nome as aluno, a.user_genero as genero, 
                    t.nome as turma, t.serie, e.nome as escola,
                    adv.data, ta.nome as tipo_advertencia, ta.gravidade,
                    adv.descricao, u.nome as registrado_por
              FROM advertencias adv
              JOIN alunos a ON adv.aluno_id = a.id
              JOIN tipos_advertencia ta ON adv.tipo_id = ta.id
              LEFT JOIN turmas t ON a.turma_id = t.id
              LEFT JOIN escolas e ON t.escola_id = e.id OR a.escola_id = e.id
              JOIN usuarios u ON adv.usuario_id = u.id
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if ($filtro_escola > 0) {
        $query .= " AND (a.escola_id = ? OR t.escola_id = ?)";
        $params[] = $filtro_escola;
        $params[] = $filtro_escola;
        $types .= "ii";
    }
    
    if ($filtro_turma > 0) {
        $query .= " AND a.turma_id = ?";
        $params[] = $filtro_turma;
        $types .= "i";
    }
    
    if ($filtro_aluno > 0) {
        $query .= " AND adv.aluno_id = ?";
        $params[] = $filtro_aluno;
        $types .= "i";
    }
    
    if ($filtro_tipo > 0) {
        $query .= " AND adv.tipo_id = ?";
        $params[] = $filtro_tipo;
        $types .= "i";
    }
    
    $query .= " ORDER BY adv.data DESC, a.nome";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dados = [];
    while ($row = $result->fetch_assoc()) {
        // Formatar data da advertência
        if (!empty($row['data'])) {
            $row['data'] = formatarData($row['data']);
        }
        $dados[] = $row;
    }
    
    exportarParaExcel($dados, 'advertencias_' . date('Y-m-d'));
}

$conn->close();

// Se chegou até aqui, não foi possível exportar
header("Location: index.php");
exit;
?>