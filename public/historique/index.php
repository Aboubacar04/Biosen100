<?php
/**
 * BIOSEN100 — Historique Ancien Système
 * Interface de consultation des commandes, factures et clients
 * URL: https://biosen100.shop/historique/
 */

// ══ CONFIG DB ════════════════════════════════════
$host = '127.0.0.1';
$db   = 'ancien_shop';
$user = 'biosen100';
$pass = 'BioSen100@2026!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// ══ FILTRES ══════════════════════════════════════
$dateDebut = $_GET['date_debut'] ?? '';
$dateFin   = $_GET['date_fin'] ?? '';
$search    = $_GET['search'] ?? '';
$statut    = $_GET['statut'] ?? '';
$page      = max(1, intval($_GET['page'] ?? 1));
$perPage   = 50;
$offset    = ($page - 1) * $perPage;

// ══ REQUÊTE ═════════════════════════════════════
$where = "WHERE c.deleted_at IS NULL";
$params = [];

if ($dateDebut) {
    $where .= " AND DATE(c.date_commande) >= :date_debut";
    $params[':date_debut'] = $dateDebut;
}
if ($dateFin) {
    $where .= " AND DATE(c.date_commande) <= :date_fin";
    $params[':date_fin'] = $dateFin;
}
if ($statut === 'payee') {
    $where .= " AND c.is_billed = 1";
} elseif ($statut === 'non_payee') {
    $where .= " AND c.is_billed = 0";
}
if ($search) {
    $where .= " AND (cl.name LIKE :search OR cl.phone LIKE :search2 OR c.code LIKE :search3)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
    $params[':search3'] = "%$search%";
}

// Count total
$countSql = "SELECT COUNT(*) FROM commande_clients c LEFT JOIN clients cl ON cl.id = c.client_id $where";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalRows = $stmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Résumé
$resumeSql = "SELECT 
    COUNT(*) as total_commandes,
    SUM(CASE WHEN f.montant_facture IS NOT NULL THEN f.montant_facture ELSE 0 END) as total_montant,
    SUM(CASE WHEN f.montant_encaisse IS NOT NULL THEN f.montant_encaisse ELSE 0 END) as total_encaisse,
    SUM(CASE WHEN c.is_billed = 1 THEN 1 ELSE 0 END) as nb_facturees,
    SUM(CASE WHEN c.is_billed = 0 THEN 1 ELSE 0 END) as nb_non_facturees
FROM commande_clients c 
LEFT JOIN clients cl ON cl.id = c.client_id 
LEFT JOIN facture_clients f ON f.commande_client_id = c.id AND f.deleted_at IS NULL
$where";
$stmt = $pdo->prepare($resumeSql);
$stmt->execute($params);
$resume = $stmt->fetch(PDO::FETCH_ASSOC);

// Commandes
$sql = "SELECT 
    c.id, c.code, c.date_commande, c.is_billed, c.delivered,
    cl.name as client_nom, cl.phone as client_tel, cl.adress as client_adresse,
    f.reference as facture_ref, f.montant_facture, f.montant_encaisse, f.montant_restant, f.facture_state,
    f.date_facture,
    (SELECT GROUP_CONCAT(CONCAT(a.name, ' x', acc.quantity, ' = ', acc.price * acc.quantity, 'F') SEPARATOR ' | ')
     FROM article_commande_client acc 
     LEFT JOIN articles a ON a.id = acc.article_id 
     WHERE acc.commande_client_id = c.id AND acc.deleted_at IS NULL) as produits_detail
FROM commande_clients c
LEFT JOIN clients cl ON cl.id = c.client_id
LEFT JOIN facture_clients f ON f.commande_client_id = c.id AND f.deleted_at IS NULL
$where
ORDER BY c.date_commande DESC
LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique — Ancien Système</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; }
            .print-break { page-break-after: always; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-6">

    <!-- ══ HEADER ══════════════════════════════ -->
    <div class="no-print flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0" style="background: linear-gradient(135deg, #15803D, #1a9d4a);">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-gray-800 tracking-tight">Historique Ancien Système</h1>
                <p class="text-sm text-gray-400 mt-0.5">Consultation des commandes et factures</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="https://biosen100.shop" class="px-4 py-2.5 rounded-xl text-sm font-bold text-gray-600 bg-white border-2 border-gray-200 hover:border-[#15803D]/30 transition-all">
                ← Retour Biosen100
            </a>
            <button onclick="window.print()" class="px-5 py-2.5 rounded-xl text-sm font-bold text-white transition-all" style="background: linear-gradient(145deg, #15803D, #1a9d4a);">
                🖨 Imprimer
            </button>
        </div>
    </div>

    <!-- ══ FILTRES ═════════════════════════════ -->
    <form method="GET" class="no-print rounded-2xl p-5 border border-[#15803D]/10 bg-white mb-6" style="box-shadow: 0 1px 3px rgba(21,128,61,0.04);">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-[11px] font-black text-gray-500 mb-1.5 uppercase tracking-wider">Recherche</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, téléphone, N°..."
                       class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-[#15803D]/40 transition-all"/>
            </div>
            <div>
                <label class="block text-[11px] font-black text-gray-500 mb-1.5 uppercase tracking-wider">Date début</label>
                <input type="date" name="date_debut" value="<?= htmlspecialchars($dateDebut) ?>"
                       class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-[#15803D]/40 transition-all"/>
            </div>
            <div>
                <label class="block text-[11px] font-black text-gray-500 mb-1.5 uppercase tracking-wider">Date fin</label>
                <input type="date" name="date_fin" value="<?= htmlspecialchars($dateFin) ?>"
                       class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-[#15803D]/40 transition-all"/>
            </div>
            <div>
                <label class="block text-[11px] font-black text-gray-500 mb-1.5 uppercase tracking-wider">Statut</label>
                <select name="statut" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-xl text-sm outline-none focus:border-[#15803D]/40 cursor-pointer transition-all">
                    <option value="">Tous</option>
                    <option value="payee" <?= $statut === 'payee' ? 'selected' : '' ?>>Facturées</option>
                    <option value="non_payee" <?= $statut === 'non_payee' ? 'selected' : '' ?>>Non facturées</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-3 rounded-xl text-sm font-bold text-white transition-all" style="background: linear-gradient(145deg, #15803D, #1a9d4a);">
                    Filtrer
                </button>
                <a href="?" class="px-4 py-3 rounded-xl text-sm font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all">
                    ✕
                </a>
            </div>
        </div>
    </form>

    <!-- ══ RÉSUMÉ ══════════════════════════════ -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Commandes</p>
            <p class="text-xl font-black text-gray-800 mt-1"><?= number_format($resume['total_commandes']) ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Montant total</p>
            <p class="text-lg font-black text-[#15803D] mt-1"><?= number_format($resume['total_montant']) ?> F</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Encaissé</p>
            <p class="text-lg font-black text-[#15803D] mt-1"><?= number_format($resume['total_encaisse']) ?> F</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Facturées</p>
            <p class="text-xl font-black text-green-600 mt-1"><?= $resume['nb_facturees'] ?></p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Non facturées</p>
            <p class="text-xl font-black text-red-500 mt-1"><?= $resume['nb_non_facturees'] ?></p>
        </div>
    </div>

    <!-- ══ PÉRIODE AFFICHÉE ════════════════════ -->
    <div class="mb-4">
        <p class="text-sm text-gray-500">
            <?php if ($dateDebut && $dateFin): ?>
                Période : <span class="font-bold text-gray-700"><?= date('d/m/Y', strtotime($dateDebut)) ?></span> au <span class="font-bold text-gray-700"><?= date('d/m/Y', strtotime($dateFin)) ?></span>
            <?php elseif ($dateDebut): ?>
                À partir du <span class="font-bold text-gray-700"><?= date('d/m/Y', strtotime($dateDebut)) ?></span>
            <?php elseif ($dateFin): ?>
                Jusqu'au <span class="font-bold text-gray-700"><?= date('d/m/Y', strtotime($dateFin)) ?></span>
            <?php else: ?>
                Toutes les commandes
            <?php endif; ?>
            — Page <?= $page ?>/<?= max(1, $totalPages) ?>
        </p>
    </div>

    <!-- ══ TABLEAU ═════════════════════════════ -->
    <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden" style="box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <?php if (empty($commandes)): ?>
            <div class="flex flex-col items-center justify-center py-16 gap-3">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center bg-gray-100">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-sm text-gray-400 font-medium">Aucune commande trouvée</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr style="background: rgba(21,128,61,0.04); border-bottom: 1.5px solid rgba(21,128,61,0.08);">
                            <th class="px-4 py-3 text-left text-[11px] font-black text-gray-500 uppercase tracking-wider">N°</th>
                            <th class="px-4 py-3 text-left text-[11px] font-black text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-[11px] font-black text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-4 py-3 text-left text-[11px] font-black text-gray-500 uppercase tracking-wider">Téléphone</th>
                            <th class="px-4 py-3 text-left text-[11px] font-black text-gray-500 uppercase tracking-wider">Produits</th>
                            <th class="px-4 py-3 text-right text-[11px] font-black text-gray-500 uppercase tracking-wider">Montant</th>
                            <th class="px-4 py-3 text-right text-[11px] font-black text-gray-500 uppercase tracking-wider">Encaissé</th>
                            <th class="px-4 py-3 text-right text-[11px] font-black text-gray-500 uppercase tracking-wider">Reste</th>
                            <th class="px-4 py-3 text-center text-[11px] font-black text-gray-500 uppercase tracking-wider">Facture</th>
                            <th class="px-4 py-3 text-center text-[11px] font-black text-gray-500 uppercase tracking-wider">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $i => $cmd): ?>
                        <tr class="hover:bg-gray-50 transition-colors" style="border-bottom: 1px solid rgba(0,0,0,0.04);">
                            <td class="px-4 py-3">
                                <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($cmd['code']) ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-700"><?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></p>
                                <p class="text-[11px] text-gray-400"><?= date('H:i', strtotime($cmd['date_commande'])) ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($cmd['client_nom'] ?? '—') ?></p>
                                <p class="text-[11px] text-gray-400"><?= htmlspecialchars($cmd['client_adresse'] ?? '') ?></p>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($cmd['client_tel'] ?? '—') ?></td>
                            <td class="px-4 py-3">
                                <p class="text-xs text-gray-600 max-w-[250px]"><?= htmlspecialchars($cmd['produits_detail'] ?? '—') ?></p>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <p class="text-sm font-bold text-gray-900"><?= $cmd['montant_facture'] ? number_format($cmd['montant_facture']) . ' F' : '—' ?></p>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <p class="text-sm font-bold text-[#15803D]"><?= $cmd['montant_encaisse'] ? number_format($cmd['montant_encaisse']) . ' F' : '—' ?></p>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <p class="text-sm font-bold <?= ($cmd['montant_restant'] > 0) ? 'text-red-500' : 'text-gray-400' ?>">
                                    <?= $cmd['montant_restant'] ? number_format($cmd['montant_restant']) . ' F' : '0 F' ?>
                                </p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <p class="text-xs font-bold text-gray-500"><?= htmlspecialchars($cmd['facture_ref'] ?? '—') ?></p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($cmd['is_billed']): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-green-100 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        <?= $cmd['facture_state'] ?? 'Facturée' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-100 text-amber-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Non facturée
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>
            <div class="no-print px-4 py-4 flex items-center justify-between" style="border-top: 1.5px solid rgba(21,128,61,0.08);">
                <p class="text-sm text-gray-500">
                    Page <span class="font-bold text-gray-700"><?= $page ?></span> / <span class="font-bold text-[#15803D]"><?= $totalPages ?></span>
                    (<?= number_format($totalRows) ?> résultats)
                </p>
                <div class="flex items-center gap-2">
                    <?php
                    $queryParams = $_GET;
                    if ($page > 1):
                        $queryParams['page'] = $page - 1;
                    ?>
                        <a href="?<?= http_build_query($queryParams) ?>" class="px-4 py-2 border-2 border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:border-[#15803D]/30 hover:bg-gray-50 transition-all">← Précédent</a>
                    <?php endif; ?>
                    <span class="px-3 py-2 rounded-xl text-sm font-black text-white" style="background: linear-gradient(145deg, #15803D, #1a9d4a);"><?= $page ?></span>
                    <?php
                    if ($page < $totalPages):
                        $queryParams['page'] = $page + 1;
                    ?>
                        <a href="?<?= http_build_query($queryParams) ?>" class="px-4 py-2 border-2 border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:border-[#15803D]/30 hover:bg-gray-50 transition-all">Suivant →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- ══ FOOTER ══════════════════════════════ -->
    <div class="mt-6 text-center text-xs text-gray-400">
        <p>Historique Ancien Système — Données importées depuis l'application précédente</p>
        <p class="mt-1">BioSen100 © <?= date('Y') ?></p>
    </div>

</div>

</body>
</html>
