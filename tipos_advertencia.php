<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar login e permissão de administrador
verificarLogin();
verificarPermissao('admin');

$conn = conectarBD();
$mensagem = '';

// Processar formulário de cadastro/edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nome = limparString($_POST['nome']);
    $gravidade = $_POST['gravidade'];
    $descricao = limparString($_POST['descricao']);
    
    if (empty($nome)) {
        $mensagem = gerarAlerta('O nome do tipo de advertência é obrigatório.', 'danger');
    } else {
        if ($id > 0) {
            // Atualizar tipo existente
            $query = "UPDATE tipos_advertencia SET nome = ?, gravidade = ?, descricao = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssi", $nome, $gravidade, $descricao, $id);
            
            if ($stmt->execute()) {
                $mensagem = gerarAlerta('Tipo de advertência atualizado com sucesso!', 'success');
            } else {
                $mensagem = gerarAlerta('Erro ao atualizar tipo de advertência: ' . $conn->error, 'danger');
            }
        } else {
            // Inserir novo tipo
            $query = "INSERT INTO tipos_advertencia (nome, gravidade, descricao) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $nome, $gravidade, $descricao);
            
            if ($stmt->execute()) {
                $mensagem = gerarAlerta('Tipo de advertência cadastrado com sucesso!', 'success');
            } else {
                $mensagem = gerarAlerta('Erro ao cadastrar tipo de advertência: ' . $conn->error, 'danger');
            }
        }
    }
}

// Processar exclusão
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    
    // Verificar se o tipo está sendo usado em alguma advertência
    $query_check = "SELECT COUNT(*) as total FROM advertencias WHERE tipo_id = ?";
    $stmt_check = $conn->prepare($query_check);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    
    if ($row_check['total'] > 0) {
        $mensagem = gerarAlerta('Este tipo de advertência não pode ser excluído pois está sendo usado em ' . $row_check['total'] . ' advertência(s).', 'danger');
    } else {
        $query_delete = "DELETE FROM tipos_advertencia WHERE id = ?";
        $stmt_delete = $conn->prepare($query_delete);
        $stmt_delete->bind_param("i", $id);
        
        if ($stmt_delete->execute()) {
            $mensagem = gerarAlerta('Tipo de advertência excluído com sucesso!', 'success');
        } else {
            $mensagem = gerarAlerta('Erro ao excluir tipo de advertência: ' . $conn->error, 'danger');
        }
    }
}

// Buscar tipo para edição
$tipo_edit = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $query_edit = "SELECT * FROM tipos_advertencia WHERE id = ?";
    $stmt_edit = $conn->prepare($query_edit);
    $stmt_edit->bind_param("i", $id);
    $stmt_edit->execute();
    $result_edit = $stmt_edit->get_result();
    
    if ($result_edit->num_rows > 0) {
        $tipo_edit = $result_edit->fetch_assoc();
    }
}

// Buscar todos os tipos
$query_tipos = "SELECT * FROM tipos_advertencia ORDER BY nome";
$tipos = $conn->query($query_tipos);

// Incluir cabeçalho
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-exclamation-triangle"></i> Tipos de Advertência</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTipoAdvertencia">
            <i class="fas fa-plus"></i> Novo Tipo
        </button>
    </div>
</div>

<?php echo $mensagem; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-list"></i> Tipos de Advertência Cadastrados
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Gravidade</th>
                        <th>Descrição</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($tipos && $tipos->num_rows > 0): ?>
                        <?php while ($tipo = $tipos->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $tipo['nome']; ?></td>
                                <td>
                                    <?php 
                                    $badge_class = '';
                                    switch ($tipo['gravidade']) {
                                        case 'baixa':
                                            $badge_class = 'bg-info';
                                            break;
                                        case 'média':
                                            $badge_class = 'bg-warning';
                                            break;
                                        case 'alta':
                                            $badge_class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $tipo['gravidade']; ?>
                                    </span>
                                </td>
                                <td><?php echo $tipo['descricao']; ?></td>
                                <td>
                                    <a href="tipos_advertencia.php?editar=<?php echo $tipo['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $tipo['id']; ?>)" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">Nenhum tipo de advertência cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Cadastro/Edição -->
<div class="modal fade" id="modalTipoAdvertencia" tabindex="-1" aria-labelledby="modalTipoAdvertenciaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="tipos_advertencia.php">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalTipoAdvertenciaLabel">
                        <?php echo $tipo_edit ? 'Editar Tipo de Advertência' : 'Novo Tipo de Advertência'; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <?php if ($tipo_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $tipo_edit['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required 
                               value="<?php echo $tipo_edit ? $tipo_edit['nome'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="gravidade" class="form-label">Gravidade *</label>
                        <select class="form-select" id="gravidade" name="gravidade" required>
                            <option value="baixa" <?php echo ($tipo_edit && $tipo_edit['gravidade'] == 'baixa') ? 'selected' : ''; ?>>Baixa</option>
                            <option value="média" <?php echo ($tipo_edit && $tipo_edit['gravidade'] == 'média') ? 'selected' : ''; ?>>Média</option>
                            <option value="alta" <?php echo ($tipo_edit && $tipo_edit['gravidade'] == 'alta') ? 'selected' : ''; ?>>Alta</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo $tipo_edit ? $tipo_edit['descricao'] : ''; ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Abrir modal automaticamente se estiver editando
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($tipo_edit): ?>
        var myModal = new bootstrap.Modal(document.getElementById('modalTipoAdvertencia'));
        myModal.show();
        <?php endif; ?>
    });
    
    // Função para confirmar exclusão
    function confirmarExclusao(id) {
        if (confirm('Tem certeza que deseja excluir este tipo de advertência?')) {
            window.location.href = 'tipos_advertencia.php?excluir=' + id;
        }
    }
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>