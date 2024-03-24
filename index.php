<?php
// Inclusion des fichiers nécessaires.
require_once 'blockchain/wallet.php';
require_once 'blockchain/transaction.php';
require_once 'vendor/autoload.php';

// Utilisation des classes dans l'espace de noms spécifié pour faciliter leur instanciation.
use CroinCraft\Blockchain\Wallet;
use CroinCraft\Blockchain\Transaction;

// Chemins vers les bases de données pour les portefeuilles et les transactions.
$dbWalletPath = __DIR__ . '/data/wallet.db';
$dbTransactionPath = __DIR__ . '/data/mempool.db';

// Instanciation des objets Wallet et Transaction avec les chemins vers les bases de données.
$wallet = new Wallet($dbWalletPath);
$transaction = new Transaction($dbTransactionPath);

// Initialisation des variables pour l'affichage.
$seedPhraseDisplayed = '';
$publicKeyHashed = '';
$error = '';
$transactionMessage = '';

// Traitement des requêtes POST.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['createWallet']) && !empty($_POST['password'])) {
        // Création d'un portefeuille si le mot de passe est fourni.
        $creationResult = $wallet->createWallet($_POST['password']);
        if (isset($creationResult['error'])) {
            // Gestion des erreurs lors de la création du portefeuille.
            $error = $creationResult['error'];
        } else {
            // Stockage des informations du portefeuille pour affichage.
            $seedPhraseDisplayed = $creationResult['seedPhrase'];
            $publicKeyHashed = $creationResult['publicKeyHashed'];
        }
    } elseif (isset($_POST['makeTransaction'])) {
        // Traitement de la transaction si le formulaire correspondant est soumis.
        try {
            // Création et envoi de la transaction en utilisant les données fournies par l'utilisateur.
            $senderPublicKey = $wallet->getWalletPublicKey();
            $transaction->createTransaction($_POST['amount'], $_POST['recipientPublicKey'], $_POST['walletPassword'], $senderPublicKey);
            $transactionMessage = "Transaction successfully sent.";
        } catch (Exception $e) {
            // Gestion des erreurs lors de la création de la transaction.
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Vérification de l'existence d'un portefeuille et mise à jour de l'adresse publique pour l'affichage.
$walletExists = $wallet->walletExists();
if ($walletExists) {
    $publicKeyHashed = $wallet->getWalletPublicKey();
}

// Structure HTML de la page.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CoinCraft Core</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="container">
        <img src="images/logo_bannier2.png" alt="Logo CoinCraft">
        <?php if ($walletExists): ?><a href="mining.php">Mining</a><?php endif; ?>
    </header>
    <div class="container">
        <?php if (!$walletExists): ?>
            <!-- Formulaire de création de portefeuille si aucun portefeuille n'existe. -->
            <h2>Create your CoinCraft wallet</h2>
            <form action="index.php" method="post">
                <input type="password" name="password" placeholder="Choose a secure password" required>
                <button type="submit" name="createWallet">Create the wallet</button>
            </form>
            <?php if (!empty($error)): ?>
                <!-- Affichage des erreurs. -->
                <p>Error: <?= htmlspecialchars($error); ?></p>
            <?php endif; ?>
        <?php else: ?>
            <!-- Affichage des informations du portefeuille si celui-ci existe. -->
            <p>Your CoinCraft wallet is ready. You can now start mining or making transactions.</p>
            <p>Wallet Address: <?= htmlspecialchars($publicKeyHashed); ?></p>
            <?php if (!empty($seedPhraseDisplayed)): ?>
                <!-- Affichage de la phrase de récupération si elle est définie. -->
                <p>Please securely note your Seed Phrase:<br/><b><?= htmlspecialchars($seedPhraseDisplayed); ?></b></p>
            <?php endif; ?>
            <!-- Formulaire d'envoi de transaction. -->
            <form action="index.php" method="post">
                <input type="number" name="amount" placeholder="Amount" required>
                <input type="text" name="recipientPublicKey" placeholder="Recipient Public Key" required>
                <input type="password" name="walletPassword" placeholder="Your Wallet Password" required>
                <button type="submit" name="makeTransaction">Send Transaction</button>
            </form>
        <?php endif; ?>
        <?php if (!empty($transactionMessage)): ?>
            <!-- Affichage des messages de transaction. -->
            <p class="success"><?= $transactionMessage; ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <!-- Affichage des erreurs. -->
            <p class="error"><?= $error; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
