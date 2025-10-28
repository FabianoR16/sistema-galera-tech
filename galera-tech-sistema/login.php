<?php
require_once 'config/database.php';
require_once 'includes/function.php';
iniciarSessao()
//Se ja estiver logado, redireciona para a pagina inicial
if (estaLogado()) {
    header("Location: presenca.php");
    exit;
}
// Verificar se o banco de dados esta inicializado, se não, inicializar
$tabelas_existem = false;
$conn = conectarBD();
$result = $conn->query("SHOW TABLES LIKE 'usuarios'");
$tabelas_existem = $result->num_rows > 0;
$conn->close();
if (!$tabelas_existem){
    inicializarBancoDados();
}
// Processar o formulario de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    if (empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos.";
    } else {
        $conn = conectarBD();
        $email = $conn->real_escape_string($email);
        $query = "SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = '$email' AND ativo =1";
        $result = $conn->query($query);

        if ($result->num_rows == 1) {
            $usuario = $result->fetch_assoc();
            if (password_verify($senha, $usuario['senha_hash'])){
                // Login bem-sucedido
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_perfil'] = $usuario['perfil'];

                header("Location: presenca.php");
                exit;
            } else{
                $erro = "Senha incorreta.";
            }
        } else {
            $erro = "Usuario não encontrado ou inativo.";
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Galera Tech</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilo personalizado -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .logo {
            max-width: 200px;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="text-center mb-4">
            <img src="assets/img/logo.svg" alt="Logo Galera Tech" class="logo">
        </div>
        
        <div class="card">
            <div class="card-body p-4">
                <h4 class="card-title text-center mb-4">Acesso ao Sistema</h4>
                
                <?php if (isset($erro)): ?>
                    <div class="alert alert-danger"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Entrar</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-3 text-muted">
            <small>&copy; <?php echo date('Y'); ?> Galera Tech & Apeti</small>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>