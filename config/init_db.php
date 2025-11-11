<?php
require_once 'database.php';

// Função para executar as queries SQL de criação do banco de dados
function inicializarBancoDados() {
    $conn = conectarBD();
    
    // Queries de criação das tabelas
    $queries = [
        // Tabela escolas
        "CREATE TABLE IF NOT EXISTS escolas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(120) NOT NULL
        )",
        
        // Tabela turmas
        "CREATE TABLE IF NOT EXISTS turmas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(60) NOT NULL,
            serie VARCHAR(30) NOT NULL,
            turno ENUM('manhã','tarde','noite') NOT NULL,
            escola_id INT NOT NULL,
            ano_letivo INT NOT NULL,
            FOREIGN KEY (escola_id) REFERENCES escolas(id)
        )",
        
        // Tabela alunos (verificar se já existe)
        "CREATE TABLE IF NOT EXISTS alunos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(120) NOT NULL,
            user_genero ENUM('feminino','masculino','prefiro não declarar') NOT NULL,
            turma_id INT NULL,
            escola_id INT NULL,
            data_nascimento DATE NULL,
            endereco VARCHAR(255) NULL,
            nome_responsavel VARCHAR(120) NULL,
            tel_responsavel VARCHAR(40) NULL,
            email_responsavel VARCHAR(120) NULL,
            FOREIGN KEY (turma_id) REFERENCES turmas(id),
            FOREIGN KEY (escola_id) REFERENCES escolas(id)
        )",
        
        // Tabela usuarios
        "CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(120) UNIQUE NOT NULL,
            senha_hash VARCHAR(255) NOT NULL,
            perfil ENUM('admin','coord','prof') NOT NULL DEFAULT 'prof',
            ativo TINYINT(1) NOT NULL DEFAULT 1
        )",
        
        // Tabela aulas
        "CREATE TABLE IF NOT EXISTS aulas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            data DATE NOT NULL,
            turma_id INT NOT NULL,
            tema VARCHAR(160) NULL,
            FOREIGN KEY (turma_id) REFERENCES turmas(id)
        )",
        
        // Tabela presencas
        "CREATE TABLE IF NOT EXISTS presencas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aula_id INT NOT NULL,
            aluno_id INT NOT NULL,
            status ENUM('presente','falta','atraso') NOT NULL,
            justificativa VARCHAR(255) NULL,
            registrado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            usuario_id INT NOT NULL,
            FOREIGN KEY (aula_id) REFERENCES aulas(id),
            FOREIGN KEY (aluno_id) REFERENCES alunos(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            UNIQUE KEY (aula_id, aluno_id)
        )",
        
        // Tabela tipos_advertencia
        "CREATE TABLE IF NOT EXISTS tipos_advertencia (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(80) NOT NULL,
            gravidade ENUM('baixa','média','alta') NOT NULL,
            descricao VARCHAR(255) NULL
        )",
        
        // Tabela advertencias
        "CREATE TABLE IF NOT EXISTS advertencias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            aluno_id INT NOT NULL,
            tipo_id INT NOT NULL,
            data DATE NOT NULL,
            descricao TEXT NULL,
            usuario_id INT NOT NULL,
            FOREIGN KEY (aluno_id) REFERENCES alunos(id),
            FOREIGN KEY (tipo_id) REFERENCES tipos_advertencia(id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        )"
    ];
    
    // Executar cada query
    foreach ($queries as $query) {
        if (!$conn->query($query)) {
            echo "Erro ao executar query: " . $conn->error . "<br>";
            echo "Query: " . $query . "<br><br>";
        }
    }
    
    // Inserir usuário admin padrão
    $senha_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $query = "INSERT INTO usuarios (nome, email, senha_hash, perfil) 
              SELECT 'Administrador', 'admin@galeratech.com', '$senha_hash', 'admin'
              FROM dual
              WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE email = 'admin@galeratech.com')";
    
    $conn->query($query);
    
    $conn->close();
    
    return true;
}
?>