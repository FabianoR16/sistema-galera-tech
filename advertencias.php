<?php
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar login
verificarLogin();

$conn = conectarBD();
$mensagem = '';

// Processar formulário de cadastro/edição
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $aluno_id = intval($_POST['aluno_id']);
    // $turma_id removido – turma vem do aluno
    $tipo_id = intval($_POST['tipo_id']);
    $descricao = limparString($_POST['descricao']);
    $data = formatarDataBanco($_POST['data']);
    
    if (empty($aluno_id) || empty($tipo_id) || empty($descricao) || empty($data)) {
        $mensagem = gerarAlerta('Todos os campos são obrigatórios.', 'danger');
    } else {
        // Inserir ou atualizar advertência
        if ($id > 0) {
            // Atualizar (sem turma_id) - alterar
            $sql = "UPDATE advertencias SET 
                    aluno_id = ?, 
                    tipo_id = ?, 
                    descricao = ?, 
                    data = ? 
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissi", $aluno_id, $tipo_id, $descricao, $data, $id);
            
            if ($stmt->execute()) {
                $mensagem = gerarAlerta('Advertência atualizada com sucesso!', 'success');
            } else {
                $mensagem = gerarAlerta('Erro ao atualizar advertência: ' . $conn->error, 'danger');
            }
        } else {
            // Inserir (sem turma_id)
            $usuario_id = $_SESSION['usuario_id'];
            $sql = "INSERT INTO advertencias (aluno_id, tipo_id, descricao, data, usuario_id) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissi", $aluno_id, $tipo_id, $descricao, $data, $usuario_id);
            
            if ($stmt->execute()) {
                $mensagem = gerarAlerta('Advertência registrada com sucesso!', 'success');
            } else {
                $mensagem = gerarAlerta('Erro ao registrar advertência: ' . $conn->error, 'danger');
            }
        }
    }
}

// Processar exclusão - excluir
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    
    // Verificar permissão (apenas admin e coordenador podem excluir)
    if ($_SESSION['usuario_perfil'] == 'admin' || $_SESSION['usuario_perfil'] == 'coord') {
        $sql = "DELETE FROM advertencias WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensagem = gerarAlerta('Advertência excluída com sucesso!', 'success');
        } else {
            $mensagem = gerarAlerta('Erro ao excluir advertência: ' . $conn->error, 'danger');
        }
    } else {
        $mensagem = gerarAlerta('Você não tem permissão para excluir advertências.', 'danger');
    }
}

// Buscar dados para o formulário
$alunos = [];
$sql_alunos = "SELECT id, nome FROM alunos ORDER BY nome";
$result_alunos = $conn->query($sql_alunos);
if ($result_alunos) {
    while ($row = $result_alunos->fetch_assoc()) {
        $alunos[] = $row;
    }
}

$turmas = [];
$sql_turmas = "SELECT id, nome FROM turmas ORDER BY nome";
$result_turmas = $conn->query($sql_turmas);
if ($result_turmas) {
    while ($row = $result_turmas->fetch_assoc()) {
        $turmas[] = $row;
    }
}

$tipos_advertencia = [];
$sql_tipos = "SELECT id, nome, gravidade FROM tipos_advertencia ORDER BY nome";
$result_tipos = $conn->query($sql_tipos);
if ($result_tipos) {
    while ($row = $result_tipos->fetch_assoc()) {
        $tipos_advertencia[] = $row;
    }
}

// Buscar advertências
$advertencias = [];
$sql_advertencias = "SELECT a.id, a.data, a.descricao, 
                    al.nome as aluno_nome, 
                    t.nome as turma_nome,
                    ta.nome as tipo_nome, 
                    ta.gravidade,
                    u.nome as usuario_nome
                    FROM advertencias a
                    INNER JOIN alunos al ON a.aluno_id = al.id
                    INNER JOIN turmas t ON al.turma_id = t.id
                    INNER JOIN tipos_advertencia ta ON a.tipo_id = ta.id
                    INNER JOIN usuarios u ON a.usuario_id = u.id
                    ORDER BY a.data DESC";
$result_advertencias = $conn->query($sql_advertencias);
if ($result_advertencias) {
    while ($row = $result_advertencias->fetch_assoc()) {
        $row['data_formatada'] = formatarData($row['data']);
        $advertencias[] = $row;
    }
}
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Advertências</h5>
                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdvertencia">
                    <i class="fas fa-plus"></i> Nova Advertência
                </button>
            </div>
            <div class="card-body">
                <?php if (!empty($mensagem)) echo $mensagem; ?>
                
                <?php if (empty($advertencias)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Nenhuma advertência registrada.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Data</th>
                                    <th>Aluno</th>
                                    <th>Turma</th>
                                    <th>Tipo</th>
                                    <th>Descrição</th>
                                    <th>Registrado por</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($advertencias as $adv): ?>
                                    <tr>
                                        <td><?php echo $adv['data_formatada']; ?></td>
                                        <td><?php echo htmlspecialchars($adv['aluno_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($adv['turma_nome']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                if ($adv['gravidade'] == 'alta') echo 'danger';
                                                elseif ($adv['gravidade'] == 'media') echo 'warning';
                                                else echo 'info';
                                            ?>">
                                                <?php echo htmlspecialchars($adv['tipo_nome']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($adv['descricao']); ?></td>
                                        <td><?php echo htmlspecialchars($adv['usuario_nome']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary editar-advertencia" 
                                                data-id="<?php echo $adv['id']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#modalAdvertencia">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($_SESSION['usuario_perfil'] == 'admin' || $_SESSION['usuario_perfil'] == 'coord'): ?>
                                            <a href="advertencias.php?excluir=<?php echo $adv['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Tem certeza que deseja excluir esta advertência?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
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

<!-- Modal de Cadastro/Edição -->
<div class="modal fade" id="modalAdvertencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitulo">Nova Advertência</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST" action="advertencias.php">
                <div class="modal-body">
                    <input type="hidden" name="id" id="advertencia_id" value="">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="aluno_id" class="form-label">Aluno</label>
                            <select name="aluno_id" id="aluno_id" class="form-select" required>
                                <option value="">Selecione um aluno</option>
                                <?php foreach ($alunos as $aluno): ?>
                                    <option value="<?php echo $aluno['id']; ?>">
                                        <?php echo htmlspecialchars($aluno['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="turma_id" class="form-label">Turma</label>
                            <select name="turma_id" id="turma_id" class="form-select" required>
                                <option value="">Selecione uma turma</option>
                                <?php foreach ($turmas as $turma): ?>
                                    <option value="<?php echo $turma['id']; ?>">
                                        <?php echo htmlspecialchars($turma['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="tipo_id" class="form-label">Tipo de Advertência</label>
                            <select name="tipo_id" id="tipo_id" class="form-select" required>
                                <option value="">Selecione um tipo</option>
                                <?php foreach ($tipos_advertencia as $tipo): ?>
                                    <option value="<?php echo $tipo['id']; ?>" data-gravidade="<?php echo $tipo['gravidade']; ?>">
                                        <?php echo htmlspecialchars($tipo['nome']); ?> 
                                        (<?php echo ucfirst($tipo['gravidade']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="data" class="form-label">Data</label>
                            <input type="text" class="form-control date-mask" id="data" name="data" placeholder="dd/mm/aaaa" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
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
// Inicializar máscaras para campos de data
document.addEventListener('DOMContentLoaded', function() {
    const dateMasks = document.querySelectorAll('.date-mask');
    dateMasks.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.substring(0, 8);
            
            if (value.length > 4) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4);
            } else if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            }
            
            e.target.value = value;
        });
    });
    
    // Preencher data atual no campo de data
    const hoje = new Date();
    const dia = String(hoje.getDate()).padStart(2, '0');
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const ano = hoje.getFullYear();
    document.getElementById('data').value = `${dia}/${mes}/${ano}`;
    
    // Configurar botões de edição
    const botoesEditar = document.querySelectorAll('.editar-advertencia');
    botoesEditar.forEach(botao => {
        botao.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            // Aqui você precisaria buscar os dados da advertência via AJAX
            // Por simplicidade, vamos apenas mudar o título do modal
            document.getElementById('modalTitulo').textContent = 'Editar Advertência';
            document.getElementById('advertencia_id').value = id;
            
            // Em uma implementação real, você buscaria os dados e preencheria o formulário
        });
    });
    
    // Quando abrir o modal para nova advertência
    const modalAdvertencia = document.getElementById('modalAdvertencia');
    modalAdvertencia.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (!button.classList.contains('editar-advertencia')) {
            // É um novo registro
            document.getElementById('modalTitulo').textContent = 'Nova Advertência';
            document.getElementById('advertencia_id').value = '';
            document.querySelector('form').reset();
            
            // Preencher data atual
            document.getElementById('data').value = `${dia}/${mes}/${ano}`;
        }
    });
    
    // Atualizar turma quando selecionar aluno
    document.getElementById('aluno_id').addEventListener('change', function() {
        const alunoId = this.value;
        if (alunoId) {
            // Em uma implementação real, você buscaria a turma do aluno via AJAX
            // e atualizaria o campo de turma automaticamente
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>