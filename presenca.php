<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar login
verificarLogin();

$conn = conectarBD();
$mensagem = '';

// Processar edição de aula
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_aula'])) {
    $aula_id = $_POST['aula_id'] ?? 0;
    $turma_id = $_POST['turma_id'] ?? 0;
    $data = $_POST['data'] ?? '';
    $tema = $_POST['tema'] ?? '';

    if ($aula_id > 0 && $turma_id > 0 && !empty($data)) {
        // Converter data para formato MySQL
        $data_mysql = DateTime::createFromFormat('d/m/Y', $data);
        if ($data_mysql) {
            $data_mysql = $data_mysql->format('Y-m-d');

            $stmt = $conn->prepare("UPDATE aulas SET turma_id = ?, data = ?, tema = ? WHERE id = ?");
            $stmt->bind_param("issi", $turma_id, $data_mysql, $tema, $aula_id);

            if ($stmt->execute()) {
                $mensagem = mensagem('success', 'Aula atualizada com sucesso!');
            } else {
                $mensagem = mensagem('danger', 'Erro ao atualizar aula: ' . $conn->error);
            }
            $stmt->close();
        } else {
            $mensagem = mensagem('danger', 'Data inválida. Use o formato dd/mm/aaaa.');
        }
    } else {
        $mensagem = mensagem('danger', 'Todos os campos obrigatórios devem ser preenchidos.');
    }
}

// Processar exclusão de aula
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['excluir_aula'])) {
    $aula_id = $_POST['aula_id'] ?? 0;

    if ($aula_id > 0) {
        // Iniciar transação para garantir consistência
        $conn->begin_transaction();

        try {
            // Primeiro, excluir todas as presenças relacionadas à aula
            $stmt_presencas = $conn->prepare("DELETE FROM presencas WHERE aula_id = ?");
            $stmt_presencas->bind_param("i", $aula_id);
            $stmt_presencas->execute();
            $stmt_presencas->close();

            // Depois, excluir a aula
            $stmt_aula = $conn->prepare("DELETE FROM aulas WHERE id = ?");
            $stmt_aula->bind_param("i", $aula_id);
            $stmt_aula->execute();
            $stmt_aula->close();

            // Commit da transação
            $conn->commit();
            $mensagem = mensagem('success', 'Aula e presenças relacionadas excluídas com sucesso!');
        } catch (Exception $e) {
            // Rollback em caso de erro
            $conn->rollback();
            $mensagem = mensagem('danger', 'Erro ao excluir aula: ' . $e->getMessage());
        }
    }
}

// Processar o formulário de registro de presença
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_presenca'])) {
    $aula_id = $_POST['aula_id'] ?? 0;
    $presencas = $_POST['presenca'] ?? [];

    if ($aula_id > 0 && !empty($presencas)) {
        // Iniciar transação
        $conn->begin_transaction();

        try {
            foreach ($presencas as $aluno_id => $status) {
                $justificativa = $_POST['justificativa'][$aluno_id] ?? null;

                // Verificar se já existe registro para este aluno nesta aula
                $check = $conn->prepare("SELECT id FROM presencas WHERE aula_id = ? AND aluno_id = ?");
                $check->bind_param("ii", $aula_id, $aluno_id);
                $check->execute();
                $result = $check->get_result();

                if ($result->num_rows > 0) {
                    // Atualizar registro existente
                    $stmt = $conn->prepare("UPDATE presencas SET status = ?, justificativa = ?, usuario_id = ? WHERE aula_id = ? AND aluno_id = ?");
                    $stmt->bind_param("ssiii", $status, $justificativa, $_SESSION['usuario_id'], $aula_id, $aluno_id);
                } else {
                    // Inserir novo registro
                    $stmt = $conn->prepare("INSERT INTO presencas (aula_id, aluno_id, status, justificativa, usuario_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iissi", $aula_id, $aluno_id, $status, $justificativa, $_SESSION['usuario_id']);
                }

                $stmt->execute();
                $stmt->close();
            }

            // Commit transação
            $conn->commit();
            $mensagem = mensagem('success', 'Presenças registradas com sucesso!');
        } catch (Exception $e) {
            // Rollback em caso de erro
            $conn->rollback();
            $mensagem = mensagem('danger', 'Erro ao registrar presenças: ' . $e->getMessage());
        }
    } else {
        $mensagem = mensagem('warning', 'Dados incompletos para registro de presença.');
    }
}

// Processar o formulário de criação de aula
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar_aula'])) {
    $turma_id = $_POST['turma_id'] ?? 0;
    $data = $_POST['data'] ?? '';
    $tema = $_POST['tema'] ?? '';

    if ($turma_id > 0 && !empty($data)) {
        $data_formatada = formatarDataBanco($data);

        // Verificar se já existe aula para esta turma nesta data
        $check = $conn->prepare("SELECT id FROM aulas WHERE turma_id = ? AND data = ?");
        $check->bind_param("is", $turma_id, $data_formatada);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $mensagem = mensagem('warning', 'Já existe uma aula registrada para esta turma nesta data.');
        } else {
            $stmt = $conn->prepare("INSERT INTO aulas (turma_id, data, tema) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $turma_id, $data_formatada, $tema);

            if ($stmt->execute()) {
                $aula_id = $conn->insert_id;
                $mensagem = mensagem('success', 'Aula criada com sucesso!');

                // Redirecionar para a página de registro de presença com a aula criada
                header("Location: presenca.php?aula_id=$aula_id");
                exit;
            } else {
                $mensagem = mensagem('danger', 'Erro ao criar aula: ' . $stmt->error);
            }

            $stmt->close();
        }
    } else {
        $mensagem = mensagem('warning', 'Selecione uma turma e informe a data da aula.');
    }
}

// Buscar turmas
$query_turmas = "SELECT t.id, t.nome, t.serie, t.turno, e.nome as escola_nome 
                FROM turmas t 
                JOIN escolas e ON t.escola_id = e.id 
                ORDER BY e.nome, t.serie, t.nome";
$turmas = $conn->query($query_turmas);

// Buscar aula específica se informada
$aula = null;
$alunos = [];
if (isset($_GET['aula_id']) && is_numeric($_GET['aula_id'])) {
    $aula_id = $_GET['aula_id'];

    $query_aula = "SELECT a.*, t.nome as turma_nome, t.serie, t.turno, e.nome as escola_nome 
                  FROM aulas a 
                  JOIN turmas t ON a.turma_id = t.id 
                  JOIN escolas e ON t.escola_id = e.id 
                  WHERE a.id = ?";
    $stmt = $conn->prepare($query_aula);
    $stmt->bind_param("i", $aula_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $aula = $result->fetch_assoc();

        // Buscar alunos da turma
        $query_alunos = "SELECT a.id, a.nome, a.user_genero, p.status, p.justificativa 
                        FROM alunos a 
                        LEFT JOIN presencas p ON p.aluno_id = a.id AND p.aula_id = ? 
                        WHERE a.turma_id = ? 
                        ORDER BY a.nome";
        $stmt = $conn->prepare($query_alunos);
        $stmt->bind_param("ii", $aula_id, $aula['turma_id']);
        $stmt->execute();
        $alunos = $stmt->get_result();
    }
}

// Buscar aulas recentes
$query_aulas_recentes = "SELECT a.id, a.data, a.tema, a.turma_id, t.nome as turma_nome, t.serie, e.nome as escola_nome 
                        FROM aulas a 
                        JOIN turmas t ON a.turma_id = t.id 
                        JOIN escolas e ON t.escola_id = e.id 
                        ORDER BY a.data DESC 
                        LIMIT 10";
$aulas_recentes = $conn->query($query_aulas_recentes);

// Incluir cabeçalho
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-clipboard-check"></i> Registro de Presença</h2>
    </div>
    <div class="col-md-6 text-md-end">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaAula">
            <i class="fas fa-plus-circle"></i> Nova Aula
        </button>
    </div>
</div>

<?php echo $mensagem; ?>

<?php if ($aula): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-chalkboard-teacher"></i>
                Aula: <?php echo formatarData($aula['data']); ?> -
                <?php echo $aula['escola_nome']; ?> -
                <?php echo $aula['turma_nome']; ?> (<?php echo $aula['serie']; ?>) -
                Turno: <?php echo $aula['turno']; ?>
                <?php if (!empty($aula['tema'])): ?>
                    <br><small>Tema: <?php echo $aula['tema']; ?></small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($alunos && $alunos->num_rows > 0): ?>
                <form method="post" action="">
                    <input type="hidden" name="aula_id" value="<?php echo $aula['id']; ?>">

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Status</th>
                                    <th>Justificativa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($aluno = $alunos->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php echo $aluno['nome']; ?>
                                            <?php if ($aluno['user_genero'] == 'feminino'): ?>
                                                <i class="fas fa-female text-danger"></i>
                                            <?php elseif ($aluno['user_genero'] == 'masculino'): ?>
                                                <i class="fas fa-male text-primary"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="presenca[<?php echo $aluno['id']; ?>]"
                                                    id="presente_<?php echo $aluno['id']; ?>" value="presente"
                                                    <?php echo ($aluno['status'] == 'presente' || $aluno['status'] === null) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="presente_<?php echo $aluno['id']; ?>">
                                                    <span class="text-success"><i class="fas fa-check-circle"></i> Presente</span>
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="presenca[<?php echo $aluno['id']; ?>]"
                                                    id="falta_<?php echo $aluno['id']; ?>" value="falta"
                                                    <?php echo ($aluno['status'] == 'falta') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="falta_<?php echo $aluno['id']; ?>">
                                                    <span class="text-danger"><i class="fas fa-times-circle"></i> Falta</span>
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="presenca[<?php echo $aluno['id']; ?>]"
                                                    id="atraso_<?php echo $aluno['id']; ?>" value="atraso"
                                                    <?php echo ($aluno['status'] == 'atraso') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="atraso_<?php echo $aluno['id']; ?>">
                                                    <span class="text-warning"><i class="fas fa-clock"></i> Atraso</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="justificativa[<?php echo $aluno['id']; ?>]"
                                                value="<?php echo $aluno['justificativa'] ?? ''; ?>"
                                                placeholder="Justificativa (opcional)">
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-center mt-3">
                        <button type="submit" name="registrar_presenca" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Salvar Registro de Presença
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Não há alunos cadastrados nesta turma.
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Selecione uma aula para registrar presença ou crie uma nova aula.
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Aulas Recentes -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-history"></i> Aulas Recentes</h5>
    </div>
    <div class="card-body">
        <?php if ($aulas_recentes && $aulas_recentes->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Escola</th>
                            <th>Turma</th>
                            <th>Tema</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($aula_recente = $aulas_recentes->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo formatarData($aula_recente['data']); ?></td>
                                <td><?php echo $aula_recente['escola_nome']; ?></td>
                                <td><?php echo $aula_recente['turma_nome']; ?> (<?php echo $aula_recente['serie']; ?>)</td>
                                <td><?php echo $aula_recente['tema'] ?? '-'; ?></td>
                                <td>
                                    <a href="presenca.php?aula_id=<?php echo $aula_recente['id']; ?>" class="btn btn-sm btn-primary me-1">
                                        <i class="fas fa-clipboard-check"></i> Registrar Presença
                                    </a>
                                    <button type="button" class="btn btn-sm btn-warning me-1"
                                        onclick="editarAula(<?php echo $aula_recente['id']; ?>, <?php echo $aula_recente['turma_id']; ?>, '<?php echo addslashes($aula_recente['tema']); ?>', '<?php echo formatarData($aula_recente['data']); ?>')"
                                        data-bs-toggle="modal" data-bs-target="#modalEditarAula">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta aula?')">
                                        <input type="hidden" name="aula_id" value="<?php echo $aula_recente['id']; ?>">
                                        <button type="submit" name="excluir_aula" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Não há aulas registradas recentemente.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nova Aula -->
<div class="modal fade" id="modalNovaAula" tabindex="-1" aria-labelledby="modalNovaAulaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalNovaAulaLabel"><i class="fas fa-plus-circle"></i> Nova Aula</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="post" action="presenca.php">
                <input type="hidden" name="criar_aula" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="turma_id" class="form-label">Turma</label>
                        <select class="form-select" id="turma_id" name="turma_id" required>
                            <option value="">Selecione uma turma</option>
                            <?php if ($turmas && $turmas->num_rows > 0): ?>
                                <?php while ($turma = $turmas->fetch_assoc()): ?>
                                    <option value="<?php echo $turma['id']; ?>">
                                        <?php echo $turma['escola_nome']; ?> -
                                        <?php echo $turma['nome']; ?> (<?php echo $turma['serie']; ?>) -
                                        Turno: <?php echo $turma['turno']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="data" class="form-label">Data da Aula</label>
                        <input type="text" class="form-control" id="data" name="data" placeholder="dd/mm/aaaa" required>
                    </div>
                    <div class="mb-3">
                        <label for="tema" class="form-label">Tema da Aula (opcional)</label>
                        <input type="text" class="form-control" id="tema" name="tema" placeholder="Tema ou conteúdo abordado">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="criar_aula" class="btn btn-primary">Criar Aula</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Aula -->
<div class="modal fade" id="modalEditarAula" tabindex="-1" aria-labelledby="modalEditarAulaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="modalEditarAulaLabel"><i class="fas fa-edit"></i> Editar Aula</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="post" action="presenca.php">
                <div class="modal-body">
                    <input type="hidden" name="editar_aula" value="1">
                    <input type="hidden" id="edit_aula_id" name="aula_id">
                    <div class="mb-3">
                        <label for="edit_turma_id" class="form-label">Turma</label>
                        <select class="form-select" id="edit_turma_id" name="turma_id" required>
                            <option value="">Selecione uma turma</option>
                            <?php
                            // Recriar query de turmas para o modal de edição
                            $query_turmas_edit = "SELECT t.id, t.nome, t.serie, t.turno, e.nome as escola_nome 
                                                 FROM turmas t 
                                                 JOIN escolas e ON t.escola_id = e.id 
                                                 ORDER BY e.nome, t.serie, t.nome";
                            $turmas_edit = $conn->query($query_turmas_edit);

                            if ($turmas_edit && $turmas_edit->num_rows > 0): ?>
                                <?php while ($turma = $turmas_edit->fetch_assoc()): ?>
                                    <option value="<?php echo $turma['id']; ?>">
                                        <?php echo $turma['escola_nome']; ?> -
                                        <?php echo $turma['nome']; ?> (<?php echo $turma['serie']; ?>) -
                                        Turno: <?php echo $turma['turno']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_data" class="form-label">Data da Aula</label>
                        <input type="text" class="form-control" id="edit_data" name="data" placeholder="dd/mm/aaaa" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_tema" class="form-label">Tema da Aula (opcional)</label>
                        <input type="text" class="form-control" id="edit_tema" name="tema" placeholder="Tema ou conteúdo abordado">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_aula" class="btn btn-warning">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Função para editar aula
    function editarAula(aulaId, turmaId, tema, data) {
        // Preencher campos do modal
        document.getElementById('edit_aula_id').value = aulaId;
        document.getElementById('edit_turma_id').value = turmaId;
        document.getElementById('edit_tema').value = tema || '';
        document.getElementById('edit_data').value = data;
    }

    // Inicializar datepicker para o campo de data
    // document.addEventListener('DOMContentLoaded', function() {
    //     // Aqui você pode adicionar código para inicializar um datepicker se necessário
    //     // Por exemplo, usando jQuery UI ou outro plugin
    // });
</script>

<?php
// Encerrar conexão após renderizar todo conteúdo que depende do BD
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
include 'includes/footer.php'; ?>