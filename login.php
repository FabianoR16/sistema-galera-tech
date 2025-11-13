<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
iniciarSessao();
if (estaLogado()) {
  header("Location: presenca.php");
  exit;
}

$tabelas_existem = false;
$conn = conectarBD();
$result = $conn->query("SHOW TABLES LIKE 'usuarios'");
$tabelas_existem = $result->num_rows > 0;
$conn->close();
if (!$tabelas_existem) {
  inicializarBancoDados();
}

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

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    html,
    body {
      height: 100%;
      transition: background 0.8s ease, color 0.8s ease;
    }

    body {
      margin: 0 !important;
      font-family: "Poppins", sans-serif !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      background: linear-gradient(135deg, #E3F2FD 0%, #90CAF9 100%) !important;
    }

    .login-wrapper {
      width: 100%;
      max-width: 420px;
      padding: 20px;
    }

    .custom-card {
      background: #ffffff !important;
      border-radius: 14px !important;
      box-shadow: 0 6px 20px rgba(73, 73, 73, 1) !important;
      padding: 30px !important;
      text-align: center !important;
      transition: background 0.8s ease, box-shadow 0.8s ease;
    }

    .logo {
      max-width: 180px;
      margin: 0 auto 4px auto;
      display: block;
    }

    .input-group-text {
      background: #f5f6fb !important;
      border: 1px solid rgba(0, 0, 0, 0.48) !important;
      border-radius: 8px 0 0 8px !important;
    }

    .form-control {
      border-radius: 0 !important;
      border: 1px solid rgba(0, 0, 0, 0.48) !important;
      border-left: none;
      box-shadow: none !important;
      height: 42px !important;
      width: auto;
      border-top-right-radius: 0 !important;
      border-bottom-right-radius: 0 !important;
    }

    .btn-primary {
      background: linear-gradient(135deg, #3056eeff 0%, #4caaf8ff 100%) !important;
      border-color: #006eff !important;
      border-radius: 8px !important;
      padding: 10px 14px !important;
      font-weight: 600 !important;
      transition: transform .18s ease, background .18s ease !important;
    }

    .btn-primary.loading {
      pointer-events: none;
      opacity: 0.8;
    }

    .btn-primary.loading::after {
      content: "";
      position: absolute;
      top: 50%;
      left: 50%;
      width: 18px;
      height: 18px;
      border: 2px solid #fff;
      border-top-color: transparent;
      border-radius: 50%;
      transform: translate(-50%, -50%);
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to {
        transform: translate(-50%, -50%) rotate(360deg);
      }
    }


    .toggle-password {
      cursor: pointer;
      border-radius: 0 8px 8px 0 !important;
      background: #f5f6fb !important;
      border: 1px solid rgba(0, 0, 0, 0.48);
      border-left: none !important;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 45px;


      transition: background-color 0.2s, color 0.2s;
    }

    .toggle-password:hover {
      background: #e0e1e6 !important;
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
      background: linear-gradient(135deg, #2e2e2eff 0%, #0c0c0cff 100%) !important;
      color: #131313ff !important;
      font-family: "Poppins", sans-serif !important;
      box-shadow: 4px 4px 10px red;
    }

    body.dark .custom-card {
      background: linear-gradient(145deg, #323232ff, #000000ff) !important;
      border: 1px solid #1e1e1e !important;
      box-shadow: 0 4px 12px rgba(246, 246, 246, 0.9), 0 0 20px rgba(204, 204, 204, 0.53), inset 0 0 30px rgba(255, 255, 255, 0.015) !important;
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
      color: #101010ff;
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
      box-shadow: 0 4px 10px rgba(255, 255, 255, 0.15);
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



    @media (max-width: 480px) {
      .custom-card {
        padding: 18px;
      }
    }
  </style>
</head>

<body>
  <button id="theme-toggle"><i class="fa-solid fa-moon"></i></button>

  <div class="login-wrapper">
    <div class="custom-card">
      <img src="assets/img/logoapeti.png" alt="Logo Galera Tech / Apeti" class="logo">
      <h4>Acesso ao Sistema</h4>

      <?php if (isset($erro)): ?>
        <div class="alert alert-danger p-2"><?php echo $erro; ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <div class="mb-3 text-start">
          <label for="email" class="form-label">E-mail</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" required placeholder="Digite seu e-mail">
          </div>
        </div>

        <div class="mb-3 text-start">
          <label for="senha" class="form-label">Senha</label>
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-lock"></i></span>
            <input type="password" class="form-control" id="senha" name="senha" required placeholder="Digite sua senha">
            <span class="toggle-password"><i class="fa-solid fa-eye"></i></span>
          </div>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary" id="login-btn">Entrar</button>
        </div>
      </form>

      <div class="small-muted mt-2">
        Login: admin@galeratech.com — Senha: admin123 <br>
        &copy; <?php echo date('Y'); ?> Galera Tech & Apeti
      </div>
    </div>
  </div>

  <script>
    // ===== MODO ESCURO COM TRANSIÇÃO =====
    const toggle = document.getElementById("theme-toggle");
    const body = document.body;
    const icon = toggle.querySelector("i");

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

    // ===== MOSTRAR / ESCONDER SENHA =====
    const senhaInput = document.getElementById("senha");
    const toggleSenha = document.querySelector(".toggle-password i");

    document.querySelector(".toggle-password").addEventListener("click", () => {
      const type = senhaInput.getAttribute("type") === "password" ? "text" : "password";
      senhaInput.setAttribute("type", type);
      toggleSenha.classList.toggle("fa-eye");
      toggleSenha.classList.toggle("fa-eye-slash");
    });

    // ===== BOTÃO DE LOGIN COM SPINNER =====
    const loginBtn = document.getElementById("login-btn");
    const form = document.querySelector("form");
    form.addEventListener("submit", () => {
      loginBtn.classList.add("loading");
    });
  </script>
</body>

</html>