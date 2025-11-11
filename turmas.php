<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

verificarLogin();
verificarPermissao(['admin', 'coord']);

require_once 'includes/header.php';

$conn = conectarBD();
$mensagem = '';

// Processar formulário de turma
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $serie = trim($_POST['serie'] ?? '');
    $turno = $_POST['turno'] ?? '';
    $escola_id = $_POST['escola_id'] ?? '';
    $ano_letivo = $_POST['ano_letivo'] ?? '';
    
    // Validações
    if (empty($nome) || empty($serie) || empty($turno) || empty($escola_id) || empty($ano_letivo)) {
        $mensagem = mensagem('danger', 'Todos os campos são obrigatórios.');
    } else {
        if ($id) {
            // Editar turma
            $stmt = $conn->prepare("UPDATE turmas SET nome = ?, serie = ?, turno = ?, escola_id = ?, ano_letivo = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $nome, $serie, $turno, $escola_id, $ano_letivo, $id);
            
            if ($stmt->execute()) {
                $mensagem = mensagem('success', 'Turma atualizada com sucesso!');
            } else {
                $mensagem = mensagem('danger', 'Erro ao atualizar turma: ' . $conn->error);
            }
        } else {
            // Criar nova turma
            $stmt = $conn->prepare("INSERT INTO turmas (nome, serie, turno, escola_id, ano_letivo) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $nome, $serie, $turno, $escola_id, $ano_letivo);
            
            if ($stmt->execute()) {
                $mensagem = mensagem('success', 'Turma criada com sucesso!');
            } else {
                $mensagem = mensagem('danger', 'Erro ao criar turma: ' . $conn->error);
            }
        }
    }
}

// Processar exclusão
if (isset($_GET['excluir'])) {
    $id = $_GET['excluir'];
    
    // Verificar se há alunos vinculados
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM alunos WHERE turma_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        $mensagem = mensagem('warning', 'Não é possível excluir esta turma pois há alunos vinculados a ela.');
    } else {
        $stmt = $conn->prepare("DELETE FROM turmas WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensagem = mensagem('success', 'Turma excluída com sucesso!');
        } else {
            $mensagem = mensagem('danger', 'Erro ao excluir turma: ' . $conn->error);
        }
    }
}

// Buscar turma para edição
$turma_edicao = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM turmas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $turma_edicao = $result->fetch_assoc();
}

$escolas = [];
$sql_escolas = "SELECT id, nome FROM escolas ORDER BY nome";
$result_escolas = $conn->query($sql_escolas);
if ($result_escolas) {
    while ($row = $result_escolas->fetch_assoc()) {
        $escolas[] = $row;
    }
}

$turmas = [];
$sql_turmas = "SELECT t.id, t.nome, t.escola_id, t.ano_letivo, t.turno, e.nome as escola_nome 
              FROM turmas t 
              INNER JOIN escolas e ON t.escola_id = e.id 
              ORDER BY t.nome";
$result_turmas = $conn->query($sql_turmas);
if ($result_turmas) {
    while ($row = $result_turmas->fetch_assoc()) {
        $turmas[] = $row;
    }
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-chalkboard"></i> Gerenciamento de Turmas</h5>
                <a class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalTurma">
                    <i class="fas fa-plus"></i> Nova Turma
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($mensagem)) echo $mensagem; ?>
                
                <?php if (empty($turmas)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Nenhuma turma cadastrada.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Escola</th>
                                    <th>Ano Letivo</th>
                                    <th>Turno</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($turmas as $turma): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string)($turma['nome'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($turma['escola_nome'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($turma['ano_letivo'] ?? '')); ?></td>
                                        <td><?php echo htmlspecialchars((string)($turma['turno'] ?? '-')); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary btn-editar" 
                                                    data-bs-toggle="modal" data-bs-target="#modalTurma"
                                                    data-id="<?php echo $turma['id']; ?>"
                                                    data-nome="<?php echo htmlspecialchars($turma['nome']); ?>"
                                                    data-serie="<?php echo htmlspecialchars($turma['serie'] ?? ''); ?>"
                                                    data-turno="<?php echo $turma['turno']; ?>"
                                                    data-escola="<?php echo $turma['escola_id']; ?>"
                                                    data-ano="<?php echo $turma['ano_letivo']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <a href="?excluir=<?php echo $turma['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Tem certeza que deseja excluir esta turma?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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
</div>

<!-- Modal para Criar/Editar Turma -->
<div class="modal fade" id="modalTurma" tabindex="-1" aria-labelledby="modalTurmaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTurmaLabel">Nova Turma</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" id="turmaId" name="id">
                    
                    <div class="mb-3">
                        <label for="turmaNome" class="form-label">Nome da Turma</label>
                        <input type="text" class="form-control" id="turmaNome" name="nome" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="turmaSerie" class="form-label">Série</label>
                        <input type="text" class="form-control" id="turmaSerie" name="serie" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="turmaTurno" class="form-label">Turno</label>
                        <select class="form-select" id="turmaTurno" name="turno" required>
                            <option value="">Selecione o turno</option>
                            <option value="manhã">Manhã</option>
                            <option value="tarde">Tarde</option>
                            <option value="noite">Noite</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="turmaEscola" class="form-label">Escola</label>
                        <select class="form-select" id="turmaEscola" name="escola_id" required>
                            <option value="">Selecione a escola</option>
                            <?php foreach ($escolas as $escola): ?>
                                <option value="<?php echo $escola['id']; ?>">
                                    <?php echo htmlspecialchars($escola['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="turmaAno" class="form-label">Ano Letivo</label>
                        <input type="number" class="form-control" id="turmaAno" name="ano_letivo" 
                               min="2020" max="2030" value="<?php echo date('Y'); ?>" required>
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
document.addEventListener('DOMContentLoaded', function() {
    // Limpar modal ao abrir para nova turma
    document.getElementById('modalTurma').addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const modal = this;
        
        if (!button.classList.contains('btn-editar')) {
            // Novo registro - limpar campos
            modal.querySelector('#modalTurmaLabel').textContent = 'Nova Turma';
            modal.querySelector('#turmaId').value = '';
            modal.querySelector('#turmaNome').value = '';
            modal.querySelector('#turmaSerie').value = '';
            modal.querySelector('#turmaTurno').value = '';
            modal.querySelector('#turmaEscola').value = '';
            modal.querySelector('#turmaAno').value = '<?php echo date('Y'); ?>';
        }
    });
    
    // Carregar dados para edição
    document.querySelectorAll('.btn-editar').forEach(function(button) {
        button.addEventListener('click', function() {
            const modal = document.getElementById('modalTurma');
            
            modal.querySelector('#modalTurmaLabel').textContent = 'Editar Turma';
            modal.querySelector('#turmaId').value = this.dataset.id;
            modal.querySelector('#turmaNome').value = this.dataset.nome;
            modal.querySelector('#turmaSerie').value = this.dataset.serie;
            modal.querySelector('#turmaTurno').value = this.dataset.turno;
            modal.querySelector('#turmaEscola').value = this.dataset.escola;
            modal.querySelector('#turmaAno').value = this.dataset.ano;
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>