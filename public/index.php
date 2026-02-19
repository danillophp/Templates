<?php
require_once __DIR__ . '/../includes/functions.php';

$success = flash('success');
$error = flash('error');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME; ?> - Solicitação</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container">
    <h1>Solicitar Cata Treco</h1>

    <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error); ?></div><?php endif; ?>

    <form action="submit_request.php" method="post" enctype="multipart/form-data" id="requestForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()); ?>">
        <label>Nome completo
            <input type="text" name="full_name" required>
        </label>
        <label>Endereço
            <input type="text" name="address" id="address" required>
        </label>
        <label>CEP
            <input type="text" name="cep" id="cep" required>
        </label>
        <label>Telefone WhatsApp
            <input type="text" name="whatsapp" placeholder="(11) 99999-9999" required>
        </label>
        <label>Foto dos trecos
            <input type="file" name="photo" accept="image/*" required>
        </label>
        <label>Data e hora da coleta
            <input type="text" name="pickup_datetime" id="pickup_datetime" required>
        </label>

        <div class="consent">
            <input type="checkbox" name="consent" id="consent" required>
            <label for="consent">Autorizo o tratamento dos meus dados para execução da coleta (LGPD).</label>
        </div>

        <input type="hidden" name="latitude" id="latitude" required>
        <input type="hidden" name="longitude" id="longitude" required>

        <div id="map"></div>
        <small>O ponto no mapa pode ser ajustado manualmente arrastando o marcador.</small>

        <button type="submit">Enviar solicitação</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="../assets/js/citizen-form.js"></script>
</body>
</html>
