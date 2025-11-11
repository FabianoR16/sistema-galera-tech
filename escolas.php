<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Iniciar sessão e verificar login
iniciarSessao();
verificarLogin();

// Verificar permissões (apenas admin e coordenadores podem acessar)
if ($_SESSION['usuario_perfil'] != 'admin' && $_SESSION['usuario_perfil'] != 'coord') {
    header("Location: index.php");
    exit;
}

// Processar exclusão de escola
if (isset($_POST['excluir_escola']) && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $conn = conectarBD();
    
    // Verificar se a escola está sendo usada em turmas ou alunos
    $query = "SELECT COUNT(*) as total FROM turmas WHERE escola_id = $id";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $total_turmas = $row['total'];
    
    $query = "SELECT COUNT(*) as total FROM alunos WHERE escola_id = $id";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $total_alunos = $row['total'];
    
    if ($total_turmas > 0 || $total_alunos > 0) {
        $mensagem = [
            'tipo' => 'erro',
            'texto' => "Não é possível excluir esta escola pois existem turmas ($total_turmas) ou alunos ($total_alunos) vinculados a ela."
        ];
    } else {
        $query = "DELETE FROM escolas WHERE id = $id";
        if ($conn->query($query)) {
            $mensagem = [
                'tipo' => 'sucesso',
                'texto' => "Escola excluída com sucesso!"
            ];
        } else {
            $mensagem = [
                'tipo' => 'erro',
                'texto' => "Erro ao excluir escola: " . $conn->error
            ];
        }
    }
    
    $conn->close();
}

// Processar formulário de cadastro/edição
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_escola'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nome = trim($_POST['nome']);
    
    if (empty($nome)) {
        $mensagem = [
            'tipo' => 'erro',
            'texto' => "O nome da escola é obrigatório."
        ];
    } else {
        $conn = conectarBD();
        $nome = $conn->real_escape_string($nome);
        
        if ($id > 0) {
            // Atualizar escola existente
            $query = "UPDATE escolas SET nome = '$nome' WHERE id = $id";
            $acao = "atualizada";
        } else {
            // Inserir nova escola
            $query = "INSERT INTO escolas (nome) VALUES ('$nome')";
            $acao = "cadastrada";
        }
        
        if ($conn->query($query)) {
            $mensagem = [
                'tipo' => 'sucesso',
                'texto' => "Escola $acao com sucesso!"
            ];
        } else {
            $mensagem = [
                'tipo' => 'erro',
                'texto' => "Erro ao salvar escola: " . $conn->error
            ];
        }
        
        $conn->close();
    }
}

// Buscar escolas para listagem
$conn = conectarBD();
$query = "SELECT * FROM escolas ORDER BY nome";
$result = $conn->query($query);
$escolas = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $escolas[] = $row;
    }
}

$conn->close();

// Incluir o cabeçalho
$titulo_pagina = "Gerenciamento de Escolas";
include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2"><?php echo $titulo_pagina; ?></h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#escolaModal">
            <i class="fas fa-plus-circle me-2"></i>Nova Escola
        </button>
    </div>
    
    <?php if (isset($mensagem)): ?>
        <div class="alert alert-<?php echo $mensagem['tipo'] == 'sucesso' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
            <?php echo $mensagem['texto']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($escolas)): ?>
                <div class="alert alert-info">
                    Nenhuma escola cadastrada. Clique em "Nova Escola" para cadastrar.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($escolas as $escola): ?>
                                <tr>
                                    <td><?php echo $escola['id']; ?></td>
                                    <td><?php echo htmlspecialchars($escola['nome']); ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary editar-escola" 
                                                data-id="<?php echo $escola['id']; ?>" 
                                                data-nome="<?php echo htmlspecialchars($escola['nome']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger excluir-escola" 
                                                data-id="<?php echo $escola['id']; ?>" 
                                                data-nome="<?php echo htmlspecialchars($escola['nome']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Cadastro/Edição -->
<div class="modal fade" id="escolaModal" tabindex="-1" aria-labelledby="escolaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="escolaModalLabel">Nova Escola</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="escola_id" value="">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome da Escola</label>
                        <input type="text" class="form-control" id="nome" name="nome" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_escola" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="confirmarExclusaoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a escola <strong id="escola_nome_excluir"></strong>?</p>
                    <p class="text-danger">Esta ação não poderá ser desfeita.</p>
                    <input type="hidden" name="id" id="escola_id_excluir" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="excluir_escola" class="btn btn-danger">Excluir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configurar modal de edição
    const editarBtns = document.querySelectorAll('.editar-escola');
    editarBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            
            document.getElementById('escola_id').value = id;
            document.getElementById('nome').value = nome;
            document.getElementById('escolaModalLabel').textContent = 'Editar Escola';
            
            const escolaModal = new bootstrap.Modal(document.getElementById('escolaModal'));
            escolaModal.show();
        });
    });
    
    // Configurar modal de exclusão
    const excluirBtns = document.querySelectorAll('.excluir-escola');
    excluirBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const nome = this.getAttribute('data-nome');
            
            document.getElementById('escola_id_excluir').value = id;
            document.getElementById('escola_nome_excluir').textContent = nome;
            
            const confirmarModal = new bootstrap.Modal(document.getElementById('confirmarExclusaoModal'));
            confirmarModal.show();
        });
    });
    
    // Resetar formulário ao abrir modal para nova escola
    const escolaModal = document.getElementById('escolaModal');
    escolaModal.addEventListener('show.bs.modal', function(event) {
        if (!event.relatedTarget.classList.contains('editar-escola')) {
            document.getElementById('escola_id').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('escolaModalLabel').textContent = 'Nova Escola';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>