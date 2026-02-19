<?php
require_once __DIR__ . '/../../includes/functions.php';

require_login(['FUNCIONARIO']);
$db = db_connect();
$uid = (int) $_SESSION['user']['id'];
$stmt = $db->prepare("SELECT * FROM requests WHERE assigned_user_id = ? AND status IN ('ENCAMINHADO', 'APROVADO', 'REAGENDADO') ORDER BY pickup_datetime ASC");
$stmt->bind_param('i', $uid);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Funcionário - <?= APP_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
</head>
<body>
<div class="container wide">
    <h1>Painel do Funcionário</h1>
    <p><?= htmlspecialchars($_SESSION['user']['full_name']); ?> | <a href="../admin/logout.php">Sair</a></p>

    <?php if ($msg = flash('success')): ?><div class="alert success"><?= htmlspecialchars($msg); ?></div><?php endif; ?>

    <?php foreach ($requests as $request): ?>
        <div class="card">
            <h3>Solicitação #<?= (int) $request['id']; ?> - <?= htmlspecialchars($request['full_name']); ?></h3>
            <p><strong>Endereço:</strong> <?= htmlspecialchars($request['address']); ?> (<?= htmlspecialchars($request['cep']); ?>)</p>
            <p><strong>Data/Hora:</strong> <?= htmlspecialchars($request['pickup_datetime']); ?></p>
            <p>
                <a class="button-link" href="tel:+55<?= htmlspecialchars(sanitize_phone($request['whatsapp'])); ?>">Ligar</a>
                <a class="button-link" target="_blank" href="<?= htmlspecialchars(whatsapp_link($request['whatsapp'], 'Olá! Sou da equipe Cata Treco. Estou a caminho da coleta.')); ?>">WhatsApp</a>
                <a class="button-link" target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($request['latitude'] . ',' . $request['longitude']); ?>">Rota</a>
                <a class="button-link" target="_blank" href="../../uploads/<?= htmlspecialchars($request['photo_path']); ?>">Foto</a>
            </p>
            <div class="mini-map" data-lat="<?= htmlspecialchars((string) $request['latitude']); ?>" data-lng="<?= htmlspecialchars((string) $request['longitude']); ?>"></div>
            <form method="post" action="finalize.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="request_id" value="<?= (int) $request['id']; ?>">
                <button type="submit">FINALIZAR CATA TRECO</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="../../assets/js/employee-map.js"></script>
</body>
</html>
