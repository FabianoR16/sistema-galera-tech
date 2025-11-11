<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
// require_once 'config/init_db.php';
iniciarSessao();
// Se já estiver logado, redirecionar para a página inicial
if (estaLogado()) {
  header("Location: presenca.php");
  exit;
}
// Verificar se o banco de dados está inicializado, se não, inicializar
$tabelas_existem = false;
$conn = conectarBD();
$result = $conn->query("SHOW TABLES LIKE 'usuarios'");
$tabelas_existem = $result->num_rows > 0;
$conn->close();
if (!$tabelas_existem) {
  inicializarBancoDados();
}
// Processar o formulário de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'] ?? '';
  $senha = $_POST['senha'] ?? '';
  if (empty($email) || empty($senha)) {
    $erro = "Preencha todos os campos.";
  } else {
    $conn = conectarBD();
    $email = $conn->real_escape_string($email);
    $query = "SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = '$email' AND ativo = 1";
    $result = $conn->query($query);
    if ($result->num_rows == 1) {
      $usuario = $result->fetch_assoc();
      if (password_verify($senha, $usuario['senha_hash'])) {
        // Login bem-sucedido
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_perfil'] = $usuario['perfil'];
        header("Location: presenca.php");
        exit;
      } else {
        $erro = "Senha incorreta.";
      }
    } else {
      $erro = "Usuário não encontrado ou inativo.";
    }
    $conn->close();
  }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Sistema Galera Tech</title>

  <!-- Bootstrap CSS (mantive caso use componentes) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


  <style>
    html,
    body {
      height: 100%;
    }

    body {
      margin: 0 !important;
      font-family: "Poppins", sans-serif !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      background: linear-gradient(135deg, #E3F2FD 0%, #90CAF9 100%) !important;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }


    .login-wrapper {
      width: 100%;
      max-width: 420px;
      padding: 20px;
      box-sizing: border-box;
    }


    .custom-card {
      background: #ffffff !important;
      border-radius: 14px !important;
      box-shadow: 0 6px 18px rgba(150, 150, 150, 1) !important;
      padding: 30px !important;
      display: flex !important;
      flex-direction: column !important;
      gap: 18px !important;
      text-align: center !important;
    }

    .custom-card .logo {
      max-width: 180px;
      margin: 0 auto 4px auto;
      display: block;
    }

    .custom-card h4 {
      margin: 0 0 6px 0;
      font-weight: 600;
      color: #222;
    }


    form {
      width: 100%;
    }

    .form-label {
      font-size: 0.9rem;
      color: #333;
    }

    .input-group .input-group-text {
      background: #f5f6fb !important;
      border: 1px solid rgba(0, 0, 0, 0.48) !important;
      border-radius: 8px 0 0 8px !important;
    }

    .form-control {
      border-radius: 0 8px 8px 0 !important;
      border: 1px solid rgba(0, 0, 0, 0.48) !important;
      box-shadow: none !important;
      height: 42px !important;
    }

    .form-control:hover {
      transition: 1.7s;
      transform: scale(1.05);
    }


    .btn-primary {
      background: linear-gradient(135deg, #3056eeff 0%, #4caaf8ff 100%) !important;
      border-color: #006eff !important;
      border-radius: 8px !important;
      padding: 10px 14px !important;
      font-weight: 600 !important;
      transition: transform .18s ease, background .18s ease !important;
    }

    .btn-primary:hover {
      background: #006eff !important;
      border-color: #0055cc !important;
      transition: 1.7s;
      transform: scale(1.05);
    }

    .small-muted {
      color: rgba(0, 0, 0, 0.45);
      font-size: 12px;
      margin-top: 6px;
    }

    :placeholder-shown {
      font-size: 13px;
    }

    .alert-custom {
      background: #fff0f0 !important;
      border: 1px solid rgba(231, 76, 60, 0.12) !important;
      color: #b00020 !important;
      padding: 10px 12px !important;
      border-radius: 8px !important;
      font-size: 0.95rem !important;
    }

    /* MODO ESCURO */
    #theme-toggle {
      position: fixed;
      top: 15px;
      right: 15px;
      background: #fff;
      border: 1px solid #d0dbe7;
      width: 42px;
      height: 42px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(255, 255, 255, 0.62);
      transition: .2s;
      z-index: 999;
    }

    #theme-toggle:hover {
      transform: scale(1.08);
    }

    /* ===== DARK MODE ===== */
    body.dark {
      background: linear-gradient(135deg, #000000 0%, #0d0d0d 100%) !important;
      color: #131313ff !important;
      font-family: "Poppins", sans-serif !important;
      box-shadow: 4px 4px 10px red;
    }

    body.dark .custom-card {

      background: linear-gradient(145deg, #323232ff, #000000ff) !important;
      border: 1px solid #1e1e1e !important;
      box-shadow:
        0 4px 12px rgba(246, 246, 246, 0.9),
        0 0 20px rgba(204, 204, 204, 0.53),
        inset 0 0 30px rgba(255, 255, 255, 0.015) !important;
      backdrop-filter: blur(6px) !important;
    }

    body.dark .custom-card h4 {
      color: #171717ff;
    }

    body.dark .form-label {
      color: #020202ff;
    }

    body.dark .card-body {
      background-color: blue;
    }

    body.dark .input-group .input-group-text {
      background: #d4d4d4ff;
      border-color: #3a4558;
      color: #1f1f1fff;
    }

    body.dark .form-control {
      background: #ffffffff;
      border-color: #3a4558;
      color: #fff;
    }

    body.dark .form-control::placeholder {
      color: #111111ff;
    }

    body.dark .btn-primary {
  background: linear-gradient(135deg, #ffffff 0%, #ffffffc5 100%) !important;
  border: 1px solid #ffffff30 !important;
  color: #000 !important;
  font-weight: 600;
  transition: 0.2s ease-in-out;
    }

    body.dark .btn-primary:hover {
  background: linear-gradient(135deg, #f2f2f2 0%, #ffffff 100%) !important;
  border-color: #ffffff60 !important;
  transform: translateY(-2px);
  box-shadow: 0 4px 10px rgba(255,255,255,0.15);
    }

    body.dark .alert-custom {
      background: #3a1f1f;
      border-color: #5a2a2a;
      color: #ff6b6b;
    }


    body.dark #theme-toggle {
      background: #0d0d0d !important;
      border: 1px solid #222 !important;
      color: #bbb !important;
    }

    body.dark h1,
    body.dark h2,
    body.dark h3,
    body.dark h4,
    body.dark label {
      color: #e6e6e6 !important;
    }

    body.dark .small-muted {
      color: #666 !important;
    }

    /* Responsividade */
    @media (max-width: 480px) {
      .login-wrapper {
        padding: 12px;
      }

      .custom-card {
        padding: 18px;
      }
    }
  </style>
</head>

<body>
  <button id="theme-toggle">
    <i class="fa-solid fa-moon"></i>
  </button>

  <div class="login-wrapper">
    <div class="custom-card">

      <!-- Logo dentro do card -->
      <img src="assets/img/logoapeti.png" alt="Logo Galera Tech / Apeti" class="logo">

      <h4>Acesso ao Sistema</h4>

      <?php if (isset($erro)): ?>
        <div class="alert-custom"><?php echo $erro; ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <div class="mb-3 text-start">
          <label for="email" class="form-label">E-mail</label>
          <div class="input-group">
            <span class="input-group-text" id="basic-addon1"><i class="fas fa-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" required aria-describedby="basic-addon1" placeholder="Digite seu e-mail">
          </div>
        </div>

        <div class="mb-3 text-start">
          <label for="senha" class="form-label">Senha</label>
          <div class="input-group">
            <span class="input-group-text" id="basic-addon2"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="senha" name="senha" required aria-describedby="basic-addon2" placeholder="Digite sua senha">
          </div>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary">Entrar</button>
        </div>
      </form>

      <div class="small-muted">
        Login: admin@galeratech.com — Senha: admin123 <br>
        &copy; <?php echo date('Y'); ?> Galera Tech & Apeti
      </div>

    </div>
  </div>

  <!-- Bootstrap JS (opcional) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const toggle = document.getElementById("theme-toggle");
    const body = document.body;
    const icon = toggle.querySelector("i");

    // carregar tema salvo
    if (localStorage.getItem("theme") === "dark") {
      body.classList.add("dark");
      icon.classList.replace("fa-moon", "fa-sun");
    }

    toggle.addEventListener("click", () => {
      body.classList.toggle("dark");

      if (body.classList.contains("dark")) {
        icon.classList.replace("fa-moon", "fa-sun");
        localStorage.setItem("theme", "dark");
      } else {
        icon.classList.replace("fa-sun", "fa-moon");
        localStorage.setItem("theme", "light");
      }
    });
  </script>
</body>

</html>