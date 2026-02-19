<?php
require_once __DIR__ . '/../../includes/functions.php';

require_login(['ADMIN']);
$db = db_connect();
$result = $db->query('SELECT r.*, u.full_name AS assigned_name FROM requests r LEFT JOIN users u ON u.id = r.assigned_user_id ORDER BY r.created_at DESC');
$requests = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - <?= APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<div class="container wide">
    <h1>Painel do Administrador</h1>
    <p>Olá, <?= htmlspecialchars($_SESSION['user']['full_name']); ?> | <a href="logout.php">Sair</a></p>
    <?php if ($msg = flash('success')): ?><div class="alert success"><?= htmlspecialchars($msg); ?></div><?php endif; ?>

    <table>
        <thead>
        <tr>
            <th>#</th><th>Cidadão</th><th>Endereço</th><th>Data/Hora</th><th>Foto</th><th>Status</th><th>Encaminhado</th><th>Ações</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($requests as $request): ?>
            <tr>
                <td><?= (int) $request['id']; ?></td>
                <td><?= htmlspecialchars($request['full_name']); ?><br><?= htmlspecialchars($request['whatsapp']); ?></td>
                <td><?= htmlspecialchars($request['address']); ?> - <?= htmlspecialchars($request['cep']); ?></td>
                <td><?= htmlspecialchars($request['pickup_datetime']); ?></td>
                <td><a target="_blank" href="../../uploads/<?= htmlspecialchars($request['photo_path']); ?>">Ver foto</a></td>
                <td><?= htmlspecialchars($request['status']); ?></td>
                <td><?= htmlspecialchars($request['assigned_name'] ?? '-'); ?></td>
                <td>
                    <form action="update_request.php" method="post" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="request_id" value="<?= (int) $request['id']; ?>">
                        <select name="action" required>
                            <option value="">Ação</option>
                            <option value="APROVAR">Aprovar</option>
                            <option value="RECUSAR">Recusar</option>
                            <option value="REAGENDAR">Alterar data/hora</option>
                            <option value="ENCAMINHAR">Encaminhar funcionário</option>
                        </select>
                        <input type="datetime-local" name="new_datetime">
                        <select name="employee_id">
                            <option value="">Funcionário...</option>
                            <?php
                            $employees = $db->query("SELECT id, full_name FROM users WHERE role='FUNCIONARIO' AND is_active=1 ORDER BY full_name")->fetch_all(MYSQLI_ASSOC);
                            foreach ($employees as $employee):
                            ?>
                                <option value="<?= (int) $employee['id']; ?>"><?= htmlspecialchars($employee['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit">Salvar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
