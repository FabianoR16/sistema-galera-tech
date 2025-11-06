<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
iniciarSessao();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Galera Tech</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <header class="bg-light shadow-sm">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light">
                <div class="container-fluid">
                    <a class="navbar-brand" href="index.php">
                        <img src="assets/img/logo.png" alt="Logo Galera Tech" height="40">
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <?php if (estaLogado()): ?>
                        <div class="collapse navbar-collapse" id="navbarNav">
                            <ul class="navbar-nav me-auto">
                                <li class="nav-item">
                                    <a class="nav-link" href="presenca.php"><i class="fas fa-clipboard-check"></i> Registro de Presença</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="alunos.php"><i class="fas fa-user-graduate"></i> Alunos</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="advertencias.php"><i class="fas fa-exclamation-triangle"></i> Advertências</a>
                                </li>
                                <?php if ($_SESSION['usuario_perfil'] == 'admin'): ?>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog"></i> Administração
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="usuarios.php">Usuários</a></li>
                                            <li><a class="dropdown-item" href="escolas.php">Escolas</a></li>
                                            <li><a class="dropdown-item" href="turmas.php">Turmas</a></li>
                                            <li><a class="dropdown-item" href="tipos_advertencia.php">Tipos de Advertência</a></li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li><a class="dropdown-item" href="configuracoes.php"><i class="fas fa-cogs"></i> Configurações do Sistema</a></li>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                            </ul>
                            <div class="d-flex">
                                <span class="navbar-text me-3">
                                    <i class="fas fa-user"></i> <?php echo $_SESSION['usuario_nome']; ?>
                                </span>
                                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-sign-out-alt"></i> Sair
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="container py-4">
        <!-- Mensagens de alerta -->
        <?php if (isset($_SESSION['mensagem'])): ?>
            <?php echo $_SESSION['mensagem']; ?>
            <?php unset($_SESSION['mensagem']); ?>
        <?php endif; ?>