<?php
// Inclusion de la classe Wallet depuis son fichier
require_once 'blockchain/wallet.php';

require_once 'vendor/autoload.php';

// Utilisation de l'espace de noms de la classe Wallet pour simplifier son instanciation
use CroinCraft\Blockchain\Wallet;

// Initialisation du chemin vers la base de données des portefeuilles
$dbPath = __DIR__ . '/data/wallet.db';

// Création d'une instance de la classe Wallet
$wallet = new Wallet($dbPath);

// Initialisation des variables pour stocker les informations du portefeuille ou un message d'erreur
$seedPhraseDisplayed = '';
$publicKeyHashed = '';
$error = '';

// Traitement du formulaire de création de portefeuille
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['createWallet']) && !empty($_POST['password'])) {
    $creationResult = $wallet->createWallet($_POST['password']);

    if (isset($creationResult['error'])) {
        $error = $creationResult['error'];
    } else {
        $seedPhraseDisplayed = $creationResult['seedPhrase'];
        $publicKeyHashed = $creationResult['publicKeyHashed'];
    }
} elseif ($wallet->walletExists()) {
    // Si un portefeuille existe déjà, récupérer son adresse publique pour l'affichage
    $publicKeyHashed = $wallet->getWalletPublicKey();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>CoinCraft Core</title>
</head>
<body>
    <header class="container">
        <img src="images/logo_bannier2.png" alt="Logo CoinCraft">
        <?php if ($wallet->walletExists()): ?>
            <a href="mining.php">Mining</a>
        <?php endif; ?>
    </header>
    <div class="container">
        <?php if (!$wallet->walletExists()): ?>
            <h2>Create your CoinCraft wallet</h2>
            <form action="index.php" method="post">
                <input type="password" name="password" placeholder="Choose a secure password" required>
                <button type="submit" name="createWallet">Create the wallet</button>
            </form>
            <?php if (!empty($error)): ?>
                <p>Error: <?= htmlspecialchars($error); ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p>Your CoinCraft wallet is ready. You can now start mining or making transactions.</p>
            <p>Wallet Address: <?= htmlspecialchars($publicKeyHashed); ?></p>
            <?php if (!empty($seedPhraseDisplayed)): ?>
                <p>Please securely note your Seed Phrase:<br/><b><?= htmlspecialchars($seedPhraseDisplayed); ?></b></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
