<?php 
// Funções utilitárias para o sistema
// Iniciar sessão se não estiver ativa
function iniciarSessao() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}
// Verificar se usuário está logado
function estaLogado() {
    iniciarSessao();
    return isset($_SESSION['usuario_id']);
}
// Redirecionar se não estiver logado
function verificarLogin() {
    if (!estaLogado()) {
        header("Location: login.php");
        exit;
    }
} 
// Verificar permissão de acesso
function verificarPermissao($perfis_permitidos = ['admin', 'coord', 'prof']) {
    iniciarSessao();    
    if (!estaLogado()) {
        header("Location: login.php");
        exit;
    }    
    // Garantir que $perfis_permitidos seja um array
    if (!is_array($perfis_permitidos)) {
        $perfis_permitidos = [$perfis_permitidos];
    }    
    if (!in_array($_SESSION['usuario_perfil'], $perfis_permitidos)) {
        header("Location: acesso-negado.php");
        exit;
    }
}
// Formatar data para exibição (de Y-m-d para d/m/Y)
function formatarData($data) {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}
// Formatar data para o banco (de d/m/Y para Y-m-d)
function formatarDataBanco($data) {
    if (empty($data)) return null;
    $partes = explode('/', $data);
    if (count($partes) != 3) return null;
    return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
}
// Limpar string para evitar injeção SQL
function limparString($string) {
    $conn = conectarBD();
    $string = $conn->real_escape_string($string);
    $conn->close();
    return $string;
}
// Gerar mensagem de alerta
function mensagem($tipo, $texto) {
    return '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">
                ' . $texto . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>';
}

// Exportar dados para Excel
function exportarParaExcel($dados, $nome_arquivo) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $nome_arquivo . '.xls"');
    header('Cache-Control: max-age=0');    
    echo '<table border="1">';    
    // Cabeçalho
    if (!empty($dados)) {
        echo '<tr>';
        foreach (array_keys($dados[0]) as $coluna) {
            echo '<th>' . $coluna . '</th>';
        }
        echo '</tr>';        
        // Dados
        foreach ($dados as $linha) {
            echo '<tr>';
            foreach ($linha as $valor) {
                echo '<td>' . $valor . '</td>';
            }
            echo '</tr>';
        }
    }    
    echo '</table>';
    exit;
}

// Compatibilidade: alias para gerar mensagens nos arquivos existentes
function gerarAlerta($texto, $tipo = 'info') {
    return mensagem($tipo, $texto);
}
?>