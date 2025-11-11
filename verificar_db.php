<?php
require_once 'config/database.php';
require_once 'config/init_db.php';

echo "<h1>Verificação do Banco de Dados</h1>";

// Verificar conexão com o banco
echo "<h2>Verificando conexão com o banco de dados...</h2>";
try {
    $conn = conectarBD();
    echo "<p style='color:green'>✓ Conexão com o banco de dados estabelecida com sucesso!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Erro na conexão com o banco de dados: " . $e->getMessage() . "</p>";
    exit;
}

// Verificar se as tabelas existem
echo "<h2>Verificando tabelas...</h2>";
$tabelas = ['usuarios', 'escolas', 'turmas', 'alunos', 'aulas', 'presencas', 'tipos_advertencia', 'advertencias'];
$tabelas_faltando = [];

foreach ($tabelas as $tabela) {
    $result = $conn->query("SHOW TABLES LIKE '$tabela'");
    if ($result->num_rows == 0) {
        $tabelas_faltando[] = $tabela;
        echo "<p style='color:red'>✗ Tabela '$tabela' não encontrada</p>";
    } else {
        echo "<p style='color:green'>✓ Tabela '$tabela' encontrada</p>";
    }
}

// Verificar usuário admin
echo "<h2>Verificando usuário administrador...</h2>";
$result = $conn->query("SELECT id, email FROM usuarios WHERE email = 'admin@galeratech.com'");
if ($result->num_rows == 0) {
    echo "<p style='color:red'>✗ Usuário administrador não encontrado</p>";
    $admin_existe = false;
} else {
    $admin = $result->fetch_assoc();
    echo "<p style='color:green'>✓ Usuário administrador encontrado (ID: {$admin['id']})</p>";
    $admin_existe = true;
}

// Inicializar banco de dados se necessário
if (!empty($tabelas_faltando) || !$admin_existe) {
    echo "<h2>Inicializando banco de dados...</h2>";
    inicializarBancoDados();
    echo "<p style='color:green'>✓ Banco de dados inicializado com sucesso!</p>";
    
    // Verificar novamente o usuário admin
    if (!$admin_existe) {
        $result = $conn->query("SELECT id, email FROM usuarios WHERE email = 'admin@galeratech.com'");
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            echo "<p style='color:green'>✓ Usuário administrador criado com sucesso (ID: {$admin['id']})</p>";
            echo "<p><strong>Email:</strong> admin@galeratech.com</p>";
            echo "<p><strong>Senha:</strong> admin123</p>";
        } else {
            echo "<p style='color:red'>✗ Falha ao criar usuário administrador</p>";
            
            // Tentar criar manualmente
            $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
            $query = "INSERT INTO usuarios (nome, email, senha_hash, perfil, ativo) 
                      VALUES ('Administrador', 'admin@galeratech.com', '$senha_hash', 'admin', 1)";
            
            if ($conn->query($query)) {
                echo "<p style='color:green'>✓ Usuário administrador criado manualmente com sucesso!</p>";
                echo "<p><strong>Email:</strong> admin@galeratech.com</p>";
                echo "<p><strong>Senha:</strong> admin123</p>";
            } else {
                echo "<p style='color:red'>✗ Erro ao criar usuário manualmente: " . $conn->error . "</p>";
            }
        }
    }
} else {
    echo "<h2>Tudo está correto!</h2>";
    echo "<p>O banco de dados está configurado corretamente e o usuário administrador existe.</p>";
    echo "<p><strong>Email:</strong> admin@galeratech.com</p>";
    echo "<p><strong>Senha:</strong> admin123</p>";
}

echo "<p><a href='login.php' class='btn btn-primary'>Voltar para o Login</a></p>";

$conn->close();
?>