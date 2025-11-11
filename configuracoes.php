<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Iniciar sessão e verificar login
iniciarSessao();
verificarLogin();

// Verificar permissões (apenas admin pode acessar)
if ($_SESSION['usuario_perfil'] != 'admin') {
    header("Location: index.php");
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_config'])) {
    $nome_sistema = trim($_POST['nome_sistema']);
    $email_contato = trim($_POST['email_contato']);
    $ano_letivo_atual = (int)$_POST['ano_letivo_atual'];
    $dias_limite_justificativa = (int)$_POST['dias_limite_justificativa'];
    
    if (empty($nome_sistema) || empty($email_contato) || $ano_letivo_atual <= 0) {
        $mensagem = ['tipo' => 'erro', 'texto' => "Todos os campos são obrigatórios."];
    } else {
        $conn = conectarBD();
        
        // Verificar se a tabela de configurações existe
        $result = $conn->query("SHOW TABLES LIKE 'configuracoes'");
        if ($result->num_rows == 0) {
            // Criar tabela de configurações
            $query = "CREATE TABLE configuracoes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chave VARCHAR(50) NOT NULL UNIQUE,
                valor TEXT NOT NULL,
                descricao VARCHAR(255) NULL
            )";
            $conn->query($query);
        }
        
        // Função para salvar configuração
        function salvarConfig($conn, $chave, $valor, $descricao) {
            $chave = $conn->real_escape_string($chave);
            $valor = $conn->real_escape_string($valor);
            $descricao = $conn->real_escape_string($descricao);
            
            $query = "INSERT INTO configuracoes (chave, valor, descricao) 
                      VALUES ('$chave', '$valor', '$descricao')
                      ON DUPLICATE KEY UPDATE valor = '$valor', descricao = '$descricao'";
            return $conn->query($query);
        }
        
        // Salvar configurações
        $sucesso = true;
        $sucesso &= salvarConfig($conn, 'nome_sistema', $nome_sistema, 'Nome do sistema');
        $sucesso &= salvarConfig($conn, 'email_contato', $email_contato, 'Email de contato');
        $sucesso &= salvarConfig($conn, 'ano_letivo_atual', $ano_letivo_atual, 'Ano letivo atual');
        $sucesso &= salvarConfig($conn, 'dias_limite_justificativa', $dias_limite_justificativa, 'Dias limite para justificativa de falta');
        
        if ($sucesso) {
            $mensagem = ['tipo' => 'sucesso', 'texto' => "Configurações salvas com sucesso!"];
        } else {
            $mensagem = ['tipo' => 'erro', 'texto' => "Erro ao salvar configurações: " . $conn->error];
        }
        
        $conn->close();
    }
}

// Buscar configurações atuais
$conn = conectarBD();
$configs = [
    'nome_sistema' => 'Sistema de Gestão Escolar - Galera Tech',
    'email_contato' => 'contato@galeratech.com',
    'ano_letivo_atual' => date('Y'),
    'dias_limite_justificativa' => 5
];

$result = $conn->query("SHOW TABLES LIKE 'configuracoes'");
if ($result->num_rows > 0) {
    $result = $conn->query("SELECT chave, valor FROM configuracoes");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $configs[$row['chave']] = $row['valor'];
        }
    }
}
$conn->close();

// Incluir cabeçalho
$titulo_pagina = "Configurações do Sistema";
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2"><?php echo $titulo_pagina; ?></h1>
    </div>
    
    <?php if (isset($mensagem)): ?>
        <div class="alert alert-<?php echo $mensagem['tipo'] == 'sucesso' ? 'success' : 'danger'; ?> alert-dismissible fade show">
            <?php echo $mensagem['texto']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome_sistema" class="form-label">Nome do Sistema</label>
                        <input type="text" class="form-control" id="nome_sistema" name="nome_sistema" 
                               value="<?php echo htmlspecialchars($configs['nome_sistema']); ?>" required>
                        <div class="form-text">Nome exibido no cabeçalho e rodapé do sistema</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email_contato" class="form-label">Email de Contato</label>
                        <input type="email" class="form-control" id="email_contato" name="email_contato" 
                               value="<?php echo htmlspecialchars($configs['email_contato']); ?>" required>
                        <div class="form-text">Email para contato e suporte</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ano_letivo_atual" class="form-label">Ano Letivo Atual</label>
                        <input type="number" class="form-control" id="ano_letivo_atual" name="ano_letivo_atual" 
                               min="2000" max="2100" value="<?php echo (int)$configs['ano_letivo_atual']; ?>" required>
                        <div class="form-text">Ano letivo padrão para novas turmas e aulas</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="dias_limite_justificativa" class="form-label">Dias para Justificativa</label>
                        <input type="number" class="form-control" id="dias_limite_justificativa" name="dias_limite_justificativa" 
                               min="1" max="30" value="<?php echo (int)$configs['dias_limite_justificativa']; ?>" required>
                        <div class="form-text">Número de dias limite para justificar faltas após a aula</div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <h4 class="mb-3">Manutenção do Sistema</h4>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Backup do Banco de Dados</h5>
                                <p class="card-text">Faça backup do banco de dados para evitar perda de informações.</p>
                                <a href="#" class="btn btn-primary" id="btn-backup">
                                    <i class="fas fa-database me-2"></i>Gerar Backup
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Verificar Banco de Dados</h5>
                                <p class="card-text">Verifique a integridade do banco de dados e corrija problemas.</p>
                                <a href="verificar_db.php" class="btn btn-info">
                                    <i class="fas fa-check-circle me-2"></i>Verificar DB
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" name="salvar_config" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Função para gerar backup (simulado)
    document.getElementById('btn-backup').addEventListener('click', function(e) {
        e.preventDefault();
        alert('Funcionalidade de backup em desenvolvimento. Em uma implementação real, isso geraria um arquivo SQL com o backup do banco de dados.');
    });
});
</script>

<?php include 'includes/footer.php'; ?>