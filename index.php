<?php
require_once __DIR__ . '/secure.php';
require_once __DIR__ . '/config/config.php';

// Récupérer la liste des clients
$stmt = $pdo->query("SELECT * FROM clients ORDER BY id ASC");
$clients = $stmt->fetchAll();

$csrf_token = get_csrf_token();
$total_clients = count($clients);
$max_clients = 253; // 10.0.0.2 à 10.0.0.254
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WireGuard Manager</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <header class="app-header">
        <div class="header-container">
            <div class="brand">
                <span class="brand-icon">🛡️</span>
                <h1>WireGuard Manager</h1>
                <span class="badge badge-info"><?= $total_clients ?> / <?= $max_clients ?> clients</span>
            </div>
            <nav class="nav-actions">
                <a href="logout.php" class="btn btn-outline-danger">🔓 Déconnexion</a>
            </nav>
        </div>
    </header>

    <main class="main-container">
        <!-- Messages de notification -->
        <?php if (!empty($_GET['success']) && !empty($_GET['name'])): ?>
            <div class="alert alert-success">
                ✅ Client <strong><?= htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8') ?></strong> généré et activé avec succès !
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['deleted']) && !empty($_GET['name'])): ?>
            <div class="alert alert-warning">
                🗑️ Client <strong><?= htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8') ?></strong> supprimé de WireGuard et de la base de données.
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['error'])): ?>
            <div class="alert alert-danger">
                ⚠️ <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_GET['sync_info'])): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($_GET['sync_info'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Carte : Ajouter un nouveau client -->
        <section class="card form-card">
            <h2>➕ Nouveau Client WireGuard</h2>
            <form action="generate_client.php" method="post" class="add-client-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="form-row">
                    <div class="form-field">
                        <label for="client_name">Nom du client :</label>
                        <input type="text" id="client_name" name="client_name" placeholder="ex: laptop-alice, phone-bob" pattern="[a-zA-Z0-9_-]{1,32}" title="Lettres, chiffres, tirets et underscores uniquement (max 32)" required autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        ✨ Créer & Configurer
                    </button>
                </div>
                <small class="form-help">Caractères autorisés : a-z, 0-9, tirets (-) et underscores (_).</small>
            </form>
        </section>

        <!-- Carte : Liste des clients -->
        <section class="card table-card">
            <div class="card-header">
                <h2>📋 Clients Enregistrés (<?= $total_clients ?>)</h2>
                <div class="card-header-actions">
                    <form action="sync.php" method="post" style="margin: 0; display: inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Importer les clients présents dans wg0.conf dans la base de données">
                            🔄 Synchroniser avec wg0.conf
                        </button>
                    </form>
                    <?php if ($total_clients > 0): ?>
                        <div class="search-box">
                            <input type="text" id="clientSearch" placeholder="🔍 Filtrer les clients..." onkeyup="filterClients()">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($total_clients === 0): ?>
                <div class="empty-state">
                    <p>Aucun client WireGuard configuré pour le moment.</p>
                </div>
            <?php else: ?>
                <form id="exportForm" action="export_wg_clients.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="table-responsive">
                        <table id="clientsTable">
                            <thead>
                                <tr>
                                    <th width="40"><input type="checkbox" id="selectAll" title="Tout sélectionner"></th>
                                    <th width="50">ID</th>
                                    <th>Nom</th>
                                    <th>Adresse IP</th>
                                    <th>Clé Publique</th>
                                    <th>Créé le</th>
                                    <th width="190">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="selected_clients[]" value="<?= (int)$client['id'] ?>" class="client-checkbox">
                                    </td>
                                    <td><?= (int)$client['id'] ?></td>
                                    <td class="client-name"><strong><?= htmlspecialchars($client['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><span class="badge badge-ip"><?= htmlspecialchars($client['ip_address'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td>
                                        <div class="key-cell">
                                            <span class="key-text" title="<?= htmlspecialchars($client['public_key'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars(substr($client['public_key'], 0, 16) . '...', ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                            <button type="button" class="btn-icon" onclick="copyKey('<?= htmlspecialchars($client['public_key'], ENT_QUOTES, 'UTF-8') ?>', this)" title="Copier la clé">
                                                📋
                                            </button>
                                        </div>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($client['created_at'])) ?></td>
                                    <td class="actions-cell">
                                        <!-- Téléchargement individuel -->
                                        <button type="submit" name="single_client_id" value="<?= (int)$client['id'] ?>" class="btn btn-sm btn-outline-primary" title="Télécharger le fichier .conf">
                                            📥 .conf
                                        </button>

                                        <!-- Suppression sécurisée par POST -->
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete(<?= (int)$client['id'] ?>, '<?= htmlspecialchars(addslashes($client['name']), ENT_QUOTES, 'UTF-8') ?>')" title="Supprimer ce client">
                                            🗑️ Supprimer
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer">
                        <button type="submit" class="btn btn-success" id="exportBtn">
                            📦 Télécharger la sélection (ZIP)
                        </button>
                    </div>
                </form>

                <!-- Formulaire caché pour la suppression sécurisée par POST -->
                <form id="deleteForm" action="delete.php" method="post" style="display: none;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" id="deleteClientId" value="">
                </form>
            <?php endif; ?>
        </section>
    </main>

    <footer class="app-footer">
        <p>WireGuard Manager — Gestion sécurisée du serveur VPN</p>
    </footer>

    <script>
    // Sélection globale des cases à cocher
    const selectAll = document.getElementById("selectAll");
    if (selectAll) {
        selectAll.addEventListener("change", function() {
            const checkboxes = document.querySelectorAll(".client-checkbox");
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                if (row.style.display !== 'none') {
                    cb.checked = this.checked;
                }
            });
        });
    }

    // Filtrer les clients dans le tableau en temps réel
    function filterClients() {
        const input = document.getElementById("clientSearch");
        const filter = input.value.toLowerCase();
        const rows = document.querySelectorAll("#clientsTable tbody tr");

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? "" : "none";
        });
    }

    // Confirmation de suppression avec formulaire POST
    function confirmDelete(id, name) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer le client "${name}" ?\nCette action supprimera également sa clé du serveur WireGuard.`)) {
            const deleteForm = document.getElementById("deleteForm");
            document.getElementById("deleteClientId").value = id;
            deleteForm.submit();
        }
    }

    // Copie de la clé publique avec feedback
    function copyKey(key, button) {
        navigator.clipboard.writeText(key).then(() => {
            const originalText = button.innerHTML;
            button.innerHTML = "✅";
            setTimeout(() => {
                button.innerHTML = originalText;
            }, 1500);
        }).catch(err => {
            alert("Impossible de copier la clé : " + err);
        });
    }
    </script>
</body>
</html>
