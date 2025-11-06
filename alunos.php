<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar login
verificarLogin();

$conn = conectarBD();
$mensagem = '';

// Processar formulário de cadastro/edição
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_aluno'])) {
    $id = $_POST['id'] ?? 0;
    $nome = $_POST['nome'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $cep_aluno = $_POST['cep_aluno'] ?? '';
    $cpf_aluno = $_POST['cpf_aluno'] ?? '';
    $rg_aluno = $_POST['rg_aluno'] ?? '';
    $tel_aluno = $_POST['tel_aluno'] ?? '';
    $email_aluno = $_POST['email_aluno'] ?? '';
    $turma_id = $_POST['turma_id'] ?? null;
    $escola_id = $_POST['escola_id'] ?? null;
    $nome_responsavel = $_POST['nome_responsavel'] ?? '';
    $tel_responsavel = $_POST['tel_responsavel'] ?? '';
    $email_responsavel = $_POST['email_responsavel'] ?? '';

    // Validar campos obrigatórios
    if (empty($nome) || empty($genero)) {
        $mensagem = mensagem('warning', 'Nome e gênero são campos obrigatórios.');
    } else {
        // Formatar data para o banco
        $data_nascimento_formatada = !empty($data_nascimento) ? formatarDataBanco($data_nascimento) : null;

        if ($id > 0) {
            // Atualizar aluno existente
            $stmt = $conn->prepare("UPDATE alunos SET 
                nome = ?, 
                user_genero = ?, 
                data_nascimento = ?, 
                endereco = ?, 
                cep_aluno = ?,
                cpf_aluno = ?,
                rg_aluno = ?,
                tel_aluno = ?,
                email_aluno = ?,
                turma_id = ?, 
                escola_id = ?, 
                nome_responsavel = ?, 
                tel_responsavel = ?, 
                email_responsavel = ? 
                WHERE id = ?");

            // Usando tipos 's' para simplificar o binding e evitar erros de contagem
            $stmt->bind_param(
                "sssssssssssssss",
                $nome,
                $genero,
                $data_nascimento_formatada,
                $endereco,
                $cep_aluno,
                $cpf_aluno,
                $rg_aluno,
                $tel_aluno,
                $email_aluno,
                $turma_id,
                $escola_id,
                $nome_responsavel,
                $tel_responsavel,
                $email_responsavel,
                $id
            );

            if ($stmt->execute()) {
                $mensagem = mensagem('success', 'Aluno atualizado com sucesso!');
            } else {
                $mensagem = mensagem('danger', 'Erro ao atualizar aluno: ' . $stmt->error);
            }
        } else {
            // Inserir novo aluno
            $stmt = $conn->prepare("INSERT INTO alunos (
                nome, 
                user_genero, 
                data_nascimento, 
                endereco, 
                cep_aluno,
                cpf_aluno,
                rg_aluno,
                tel_aluno,
                email_aluno,
                turma_id, 
                escola_id, 
                nome_responsavel, 
                tel_responsavel, 
                email_responsavel) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "ssssssssssssss",
                $nome,
                $genero,
                $data_nascimento_formatada,
                $endereco,
                $cep_aluno,
                $cpf_aluno,
                $rg_aluno,
                $tel_aluno,
                $email_aluno,
                $turma_id,
                $escola_id,
                $nome_responsavel,
                $tel_responsavel,
                $email_responsavel
            );

            if ($stmt->execute()) {
                $mensagem = mensagem('success', 'Aluno cadastrado com sucesso!');
                // Limpar formulário após cadastro bem-sucedido
                $_POST = array();
            } else {
                $mensagem = mensagem('danger', 'Erro ao cadastrar aluno: ' . $stmt->error);
            }
        }

        $stmt->close();
    }
}

// Buscar aluno para edição
$aluno = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = $_GET['editar'];

    $stmt = $conn->prepare("SELECT * FROM alunos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $aluno = $result->fetch_assoc();
        // Formatar data para exibição
        if (!empty($aluno['data_nascimento'])) {
            $aluno['data_nascimento'] = formatarData($aluno['data_nascimento']);
        }
    }

    $stmt->close();
}

// Excluir aluno
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = $_GET['excluir'];

    // Verificar se o aluno pode ser excluído (não tem registros relacionados)
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM presencas WHERE aluno_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['total'] > 0) {
        $mensagem = mensagem('warning', 'Não é possível excluir este aluno pois existem registros de presença associados.');
    } else {
        $stmt = $conn->prepare("DELETE FROM alunos WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $mensagem = mensagem('success', 'Aluno excluído com sucesso!');
        } else {
            $mensagem = mensagem('danger', 'Erro ao excluir aluno: ' . $stmt->error);
        }
    }

    $stmt->close();
}

// Buscar escolas
$query_escolas = "SELECT id, nome FROM escolas ORDER BY nome";
$escolas = $conn->query($query_escolas);

// Buscar turmas
$query_turmas = "SELECT t.id, t.nome, t.serie, t.turno, e.nome as escola_nome 
                FROM turmas t 
                JOIN escolas e ON t.escola_id = e.id 
                ORDER BY e.nome, t.serie, t.nome";
$turmas = $conn->query($query_turmas);

// Buscar alunos com informações de turma e escola
$filtro_escola = isset($_GET['filtro_escola']) ? (int)$_GET['filtro_escola'] : 0;
$filtro_turma = isset($_GET['filtro_turma']) ? (int)$_GET['filtro_turma'] : 0;
$filtro_genero = isset($_GET['filtro_genero']) ? $_GET['filtro_genero'] : '';

$query_alunos = "SELECT a.*, t.nome as turma_nome, t.serie, e.nome as escola_nome 
                FROM alunos a 
                LEFT JOIN turmas t ON a.turma_id = t.id 
                LEFT JOIN escolas e ON e.id = COALESCE(a.escola_id, t.escola_id) 
                WHERE 1=1";

$params = [];
$types = "";

if ($filtro_escola > 0) {
    $query_alunos .= " AND COALESCE(a.escola_id, t.escola_id) = ?";
    $params[] = $filtro_escola;
    $types .= "i";
}

if ($filtro_turma > 0) {
    $query_alunos .= " AND a.turma_id = ?";
    $params[] = $filtro_turma;
    $types .= "i";
}

if (!empty($filtro_genero)) {
    $query_alunos .= " AND a.user_genero = ?";
    $params[] = $filtro_genero;
    $types .= "s";
}

$query_alunos .= " ORDER BY a.nome";

$stmt = $conn->prepare($query_alunos);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$alunos = $stmt->get_result();

$conn->close();

// Incluir cabeçalho
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-user-graduate"></i> Cadastro de Alunos</h2>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="alunos.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Novo Aluno
        </a>
        <a href="relatorios.php?tipo=alunos" class="btn btn-info">
            <i class="fas fa-chart-bar"></i> Relatórios
        </a>
    </div>
</div>

<?php echo $mensagem; ?>

<div class="row">
    <!-- Formulário de Cadastro/Edição -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <?php echo isset($aluno) ? '<i class="fas fa-edit"></i> Editar Aluno' : '<i class="fas fa-plus-circle"></i> Novo Aluno'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <?php if (isset($aluno)): ?>
                        <input type="hidden" name="id" value="<?php echo $aluno['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required
                            value="<?php echo isset($aluno) ? $aluno['nome'] : (isset($_POST['nome']) ? $_POST['nome'] : ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gênero *</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="genero" id="genero_feminino" value="feminino" required
                                    <?php echo (isset($aluno) && $aluno['user_genero'] == 'feminino') || (isset($_POST['genero']) && $_POST['genero'] == 'feminino') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="genero_feminino">Feminino</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="genero" id="genero_masculino" value="masculino"
                                    <?php echo (isset($aluno) && $aluno['user_genero'] == 'masculino') || (isset($_POST['genero']) && $_POST['genero'] == 'masculino') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="genero_masculino">Masculino</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="genero" id="genero_outro" value="prefiro não declarar"
                                    <?php echo (isset($aluno) && $aluno['user_genero'] == 'prefiro não declarar') || (isset($_POST['genero']) && $_POST['genero'] == 'prefiro não declarar') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="genero_outro">Prefiro não declarar</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                        <input type="text" class="form-control" id="data_nascimento" name="data_nascimento" placeholder="dd/mm/aaaa"
                            value="<?php echo isset($aluno) ? $aluno['data_nascimento'] : (isset($_POST['data_nascimento']) ? $_POST['data_nascimento'] : ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="endereco" name="endereco"
                            value="<?php echo isset($aluno) ? $aluno['endereco'] : (isset($_POST['endereco']) ? $_POST['endereco'] : ''); ?>">
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="cep_aluno" class="form-label">CEP</label>
                            <input type="text" class="form-control" id="cep_aluno" name="cep_aluno"
                                value="<?php echo isset($aluno) ? ($aluno['cep_aluno'] ?? '') : (isset($_POST['cep_aluno']) ? $_POST['cep_aluno'] : ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="cpf_aluno" class="form-label">CPF do Aluno</label>
                            <input type="text" class="form-control" id="cpf_aluno" name="cpf_aluno"
                                value="<?php echo isset($aluno) ? ($aluno['cpf_aluno'] ?? '') : (isset($_POST['cpf_aluno']) ? $_POST['cpf_aluno'] : ''); ?>">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label for="rg_aluno" class="form-label">RG do Aluno</label>
                            <input type="text" class="form-control" id="rg_aluno" name="rg_aluno"
                                value="<?php echo isset($aluno) ? ($aluno['rg_aluno'] ?? '') : (isset($_POST['rg_aluno']) ? $_POST['rg_aluno'] : ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="tel_aluno" class="form-label">Telefone do Aluno</label>
                            <input type="text" class="form-control" id="tel_aluno" name="tel_aluno"
                                value="<?php echo isset($aluno) ? ($aluno['tel_aluno'] ?? '') : (isset($_POST['tel_aluno']) ? $_POST['tel_aluno'] : ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email_aluno" class="form-label">E-mail do Aluno</label>
                        <input type="email" class="form-control" id="email_aluno" name="email_aluno"
                            value="<?php echo isset($aluno) ? ($aluno['email_aluno'] ?? '') : (isset($_POST['email_aluno']) ? $_POST['email_aluno'] : ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="escola_id" class="form-label">Escola</label>
                        <select class="form-select" id="escola_id" name="escola_id">
                            <option value="">Selecione uma escola</option>
                            <?php if ($escolas && $escolas->num_rows > 0): ?>
                                <?php while ($escola = $escolas->fetch_assoc()): ?>
                                    <option value="<?php echo $escola['id']; ?>"
                                        <?php echo (isset($aluno) && $aluno['escola_id'] == $escola['id']) || (isset($_POST['escola_id']) && $_POST['escola_id'] == $escola['id']) ? 'selected' : ''; ?>>
                                        <?php echo $escola['nome']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="turma_id" class="form-label">Turma</label>
                        <select class="form-select" id="turma_id" name="turma_id">
                            <option value="">Selecione uma turma</option>
                            <?php if ($turmas && $turmas->num_rows > 0): ?>
                                <?php while ($turma = $turmas->fetch_assoc()): ?>
                                    <option value="<?php echo $turma['id']; ?>"
                                        <?php echo (isset($aluno) && $aluno['turma_id'] == $turma['id']) || (isset($_POST['turma_id']) && $_POST['turma_id'] == $turma['id']) ? 'selected' : ''; ?>>
                                        <?php echo $turma['escola_nome']; ?> -
                                        <?php echo $turma['nome']; ?> (<?php echo $turma['serie']; ?>) -
                                        Turno: <?php echo $turma['turno']; ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <hr>
                    <h5>Dados do Responsável</h5>

                    <div class="mb-3">
                        <label for="nome_responsavel" class="form-label">Nome do Responsável</label>
                        <input type="text" class="form-control" id="nome_responsavel" name="nome_responsavel"
                            value="<?php echo isset($aluno) ? $aluno['nome_responsavel'] : (isset($_POST['nome_responsavel']) ? $_POST['nome_responsavel'] : ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="tel_responsavel" class="form-label">Telefone do Responsável</label>
                        <input type="text" class="form-control" id="tel_responsavel" name="tel_responsavel"
                            value="<?php echo isset($aluno) ? $aluno['tel_responsavel'] : (isset($_POST['tel_responsavel']) ? $_POST['tel_responsavel'] : ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="email_responsavel" class="form-label">E-mail do Responsável</label>
                        <input type="email" class="form-control" id="email_responsavel" name="email_responsavel"
                            value="<?php echo isset($aluno) ? $aluno['email_responsavel'] : (isset($_POST['email_responsavel']) ? $_POST['email_responsavel'] : ''); ?>">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="salvar_aluno" class="btn btn-success">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                        <?php if (isset($aluno)): ?>
                            <a href="alunos.php" class="btn btn-secondary">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Listagem de Alunos -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Alunos Cadastrados</h5>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <form method="get" action="" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label for="filtro_escola" class="form-label">Escola</label>
                            <select class="form-select" id="filtro_escola" name="filtro_escola">
                                <option value="">Todas as escolas</option>
                                <?php
                                $escolas->data_seek(0);
                                if ($escolas && $escolas->num_rows > 0):
                                ?>
                                    <?php while ($escola = $escolas->fetch_assoc()): ?>
                                        <option value="<?php echo $escola['id']; ?>"
                                            <?php echo isset($filtro_escola) && $filtro_escola == $escola['id'] ? 'selected' : ''; ?>>
                                            <?php echo $escola['nome']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filtro_turma" class="form-label">Turma</label>
                            <select class="form-select" id="filtro_turma" name="filtro_turma">
                                <option value="">Todas as turmas</option>
                                <?php
                                $turmas->data_seek(0);
                                if ($turmas && $turmas->num_rows > 0):
                                ?>
                                    <?php while ($turma = $turmas->fetch_assoc()): ?>
                                        <option value="<?php echo $turma['id']; ?>"
                                            <?php echo isset($filtro_turma) && $filtro_turma == $turma['id'] ? 'selected' : ''; ?>>
                                            <?php echo $turma['nome']; ?> (<?php echo $turma['serie']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filtro_genero" class="form-label">Gênero</label>
                            <select class="form-select" id="filtro_genero" name="filtro_genero">
                                <option value="">Todos</option>
                                <option value="feminino" <?php echo $filtro_genero == 'feminino' ? 'selected' : ''; ?>>Feminino</option>
                                <option value="masculino" <?php echo $filtro_genero == 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                                <option value="prefiro não declarar" <?php echo $filtro_genero == 'prefiro não declarar' ? 'selected' : ''; ?>>Prefiro não declarar</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="alunos.php" class="btn btn-outline-primary">
                            <i class="fas fa-undo"></i> Limpar Filtros
                        </a>
                        <?php if ($alunos && $alunos->num_rows > 0): ?>
                            <a href="exportar.php?tipo=alunos<?php echo !empty($filtro_escola) ? '&escola_id=' . $filtro_escola : ''; ?><?php echo !empty($filtro_turma) ? '&turma_id=' . $filtro_turma : ''; ?><?php echo !empty($filtro_genero) ? '&genero=' . $filtro_genero : ''; ?>" class="btn btn-success float-end">
                                <i class="fas fa-file-excel"></i> Exportar para Excel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($alunos && $alunos->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Gênero</th>
                                    <th>Escola</th>
                                    <th>Turma</th>
                                    <th>Responsável</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($aluno = $alunos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $aluno['nome']; ?></td>
                                        <td>
                                            <?php if ($aluno['user_genero'] == 'feminino'): ?>
                                                <i class="fas fa-female text-danger"></i> Feminino
                                            <?php elseif ($aluno['user_genero'] == 'masculino'): ?>
                                                <i class="fas fa-male text-primary"></i> Masculino
                                            <?php else: ?>
                                                <i class="fas fa-user text-secondary"></i> Não declarado
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $aluno['escola_nome'] ?? '-'; ?></td>
                                        <td><?php echo isset($aluno['turma_nome']) ? $aluno['turma_nome'] . ' (' . $aluno['serie'] . ')' : '-'; ?></td>
                                        <td><?php echo $aluno['nome_responsavel'] ?? '-'; ?></td>
                                        <td>
                                            <a href="alunos.php?editar=<?php echo $aluno['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="alunos.php?excluir=<?php echo $aluno['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este aluno?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Nenhum aluno encontrado.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Inicializar datepicker para o campo de data
    document.addEventListener('DOMContentLoaded', function() {
        // Aqui você pode adicionar código para inicializar um datepicker se necessário
    });
</script>

<?php include 'includes/footer.php'; ?>