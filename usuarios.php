<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar login e permissão de administrador
verificarLogin();
verificarPermissao('admin');

$conn = conectarBD();
$mensagem = '';

// Processar formulário de cadastro/edição de usuários
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_usuario'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $perfil = trim($_POST['perfil'] ?? 'prof');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $senha = trim($_POST['senha'] ?? '');

    if (empty($nome) || empty($email)) {
        $mensagem = mensagem('danger', 'Nome e e-mail são obrigatórios.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = mensagem('danger', 'E-mail inválido.');
    } elseif (!in_array($perfil, ['admin','coord','prof'])) {
        $mensagem = mensagem('danger', 'Perfil inválido.');
    } else {
        // Verificar e-mail único (diferente do id atual)
        $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ?');
        $stmt->bind_param('si', $email, $id);
        $stmt->execute();
        $dup = $stmt->get_result();
        if ($dup && $dup->num_rows > 0) {
            $mensagem = mensagem('danger', 'E-mail já cadastrado para outro usuário.');
        } else {
            if ($id > 0) {
                // Atualização: senha só é alterada se fornecida
                if (!empty($senha)) {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $sql = 'UPDATE usuarios SET nome = ?, email = ?, senha_hash = ?, perfil = ?, ativo = ? WHERE id = ?';
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ssssii', $nome, $email, $senha_hash, $perfil, $ativo, $id);
                } else {
                    $sql = 'UPDATE usuarios SET nome = ?, email = ?, perfil = ?, ativo = ? WHERE id = ?';
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('sssii', $nome, $email, $perfil, $ativo, $id);
                }
                if ($stmt->execute()) {
                    $mensagem = mensagem('success', 'Usuário atualizado com sucesso!');
                } else {
                    $mensagem = mensagem('danger', 'Erro ao atualizar usuário: ' . $conn->error);
                }
            } else {
                if (empty($senha)) {
                    $mensagem = mensagem('danger', 'Informe uma senha para o novo usuário.');
                } else {
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $sql = 'INSERT INTO usuarios (nome, email, senha_hash, perfil, ativo) VALUES (?, ?, ?, ?, ?)';
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ssssi', $nome, $email, $senha_hash, $perfil, $ativo);
                    if ($stmt->execute()) {
                        $mensagem = mensagem('success', 'Usuário cadastrado com sucesso!');
                    } else {
                        $mensagem = mensagem('danger', 'Erro ao cadastrar usuário: ' . $conn->error);
                    }
                }
            }
        }
    }
}

// Processar exclusão de usuário
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    // Evitar excluir o próprio usuário logado
    if ($id == $_SESSION['usuario_id']) {
        $mensagem = mensagem('warning', 'Você não pode excluir seu próprio usuário.');
    } else {
        $stmt = $conn->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $mensagem = mensagem('success', 'Usuário excluído com sucesso!');
        } else {
            $mensagem = mensagem('danger', 'Erro ao excluir usuário: ' . $conn->error);
        }
    }
}

// Buscar usuário para edição
$usuario_edit = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = intval($_GET['editar']);
    $stmt = $conn->prepare('SELECT id, nome, email, perfil, ativo FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $usuario_edit = $res->fetch_assoc();
    }
}

// Buscar todos os usuários
$usuarios = $conn->query("SELECT id, nome, email, perfil, ativo FROM usuarios ORDER BY nome");

// Incluir cabeçalho
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-users"></i> Usuários</h2>
    </div>
    <div class="col-md-6 text-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario">
            <i class="fas fa-plus"></i> Novo Usuário
        </button>
    </div>
</div>

<?php echo $mensagem; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fas fa-list"></i> Usuários Cadastrados
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Ativo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usuarios && $usuarios->num_rows > 0): ?>
                        <?php while ($usuario = $usuarios->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td><span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($usuario['perfil']); ?></span></td>
                                <td><?php echo $usuario['ativo'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>'; ?></td>
                                <td>
                                    <a href="usuarios.php?editar=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmarExclusao(<?php echo $usuario['id']; ?>)" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Nenhum usuário cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Cadastro/Edição de Usuário -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="usuarios.php">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalUsuarioLabel">
                        <?php echo $usuario_edit ? 'Editar Usuário' : 'Novo Usuário'; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <?php if ($usuario_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $usuario_edit['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo $usuario_edit ? htmlspecialchars($usuario_edit['nome']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail *</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?php echo $usuario_edit ? htmlspecialchars($usuario_edit['email']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="perfil" class="form-label">Perfil *</label>
                        <select class="form-select" id="perfil" name="perfil" required>
                            <?php $p = $usuario_edit ? $usuario_edit['perfil'] : 'prof'; ?>
                            <option value="admin" <?php echo ($p == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="coord" <?php echo ($p == 'coord') ? 'selected' : ''; ?>>Coordenação</option>
                            <option value="prof" <?php echo ($p == 'prof') ? 'selected' : ''; ?>>Professor</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha <?php echo $usuario_edit ? '(deixe vazio para manter)' : '*'; ?></label>
                        <input type="password" class="form-control" id="senha" name="senha" <?php echo $usuario_edit ? '' : 'required'; ?>>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="ativo" name="ativo" <?php echo ($usuario_edit ? (intval($usuario_edit['ativo']) ? 'checked' : '') : 'checked'); ?>>
                        <label class="form-check-label" for="ativo">Usuário ativo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" name="salvar_usuario" value="1">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Abrir modal automaticamente se estiver editando
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($usuario_edit): ?>
        var myModal = new bootstrap.Modal(document.getElementById('modalUsuario'));
        myModal.show();
        <?php endif; ?>
    });
    
    // Função para confirmar exclusão
    function confirmarExclusao(id) {
        if (confirm('Tem certeza que deseja excluir este usuário?')) {
            window.location.href = 'usuarios.php?excluir=' + id;
        }
    }
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>