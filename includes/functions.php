<?php
// FUNCOES UTILITARIAS PARA O SISTEMA
// INICIAR SESSAO SE NAO ESTIVER ABERTA
function iniciarSessao() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// VERIFICAR SE O USUARIO ESTA LOGADO
function estaLogado() {
    iniciarSessao();
    return isset($_SESSION['usuario_id']);
}
// REDIRECIONAR SE ESTIVER LOGADO
function verificarLogin() {
    if (!estaLogado()) {
        header("Location: login.php");
        exit;
    }
}

//VERIFICAR PERMISSAO DE ACESSO
function verificarPermissao($perfis_permitidos = ['admin', 'coord', 'prof']) {
    iniciarSessao();
    if (!estaLogado()) {
        header("Location: login.php");
        exit;
    }
    // Garantir que $perfil_permitidos seja um array
    if (!is_array($perfis_permitidos)) {
        $perfil_permitidos = [$perfis_permitidos];
    }

    if (!is_array($_SESSION['usuario_perfil'], $perfis_permitidos)) {
        header("Location: acesso-negado.php");
        exit;
    }
}

// FORMATAR DATA PARA EXIBICAO (DE Y-m-d para d/m/Y)
function formatarData($data) {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}
//FORMATAR DATA PARA O BANCO (de d/m/Y para Y-m-d)
function formatarDataBanco($data) {
    if (empty($data)) return null;
    $partes = explode('/', $data);
    if (count($partes) != 3) return null;
    return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
}
// LIMPAR STRING PARA EVITAR INJEÇÃO SQL
function limparString($string) {
    $conn = conectarBD();
    $string = $conn->real_escape_string($string);
    $conn->close();
    return $string;
}
// Gerar mensagem de alerta
function mensagem($tipo, $texto) {
    return '<div class="alert alert-' . $tipo . 'alert-dismissible fade show" role="alert"> 
    '.  $texto . '
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
    </div>';
}
?>