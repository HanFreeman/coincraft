<?php
require_once 'vendor/autoload.php';

use Defuse\Crypto\Crypto;
use Defuse\Crypto\KeyProtectedByPassword;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Crypto\Key\PrivateKeyInterface;
use Mdanter\Ecc\Crypto\Key\PrivateKey;

// Définit le chemin vers la base de données SQLite.
$dbPath = __DIR__ . '/data/wallet.db';
$db = new SQLite3($dbPath);

// Prépare la table de la base de données, si elle n'existe pas déjà.
$db->exec("CREATE TABLE IF NOT EXISTS wallet (id INTEGER PRIMARY KEY, public_key TEXT, private_key_encrypted TEXT, encryption_key TEXT)");

// Génère une phrase de récupération à partir d'une liste de mots.
function generateSeedPhrase($wordlistPath, $wordCount = 12) {
    $words = file($wordlistPath, FILE_IGNORE_NEW_LINES);
    if ($words === false) {
        throw new Exception("Impossible de charger la wordlist.");
    }
    $selectedWordsKeys = array_rand($words, $wordCount); // Sélectionne les clés des mots
    $selectedWords = array_intersect_key($words, array_flip($selectedWordsKeys)); // Sélectionne les mots correspondants aux clés
    return implode(' ', $selectedWords);
}

// Génère une clé maîtresse à partir de la seed phrase.
function generateMasterKey($seedPhrase) {
    // Convertit la seed phrase en hash binaire.
    $seedPhraseHash = hash('sha256', $seedPhrase, true);
    // Utilise le hash comme clé maîtresse.
    $generator = EccFactory::getNistCurves()->generator256();
    $masterKey = $generator->createPrivateKey($seedPhraseHash);
    return $masterKey;
}

// Encrypte la clé privée pour le stockage.
function encryptPrivateKey($privateKey, $password) {
    $protectedKey = KeyProtectedByPassword::createRandomPasswordProtectedKey($password);
    $encryptedPrivateKey = Crypto::encryptWithPassword((string)$privateKey->getSecret(), $password, true, $protectedKey); // Le troisième argument doit être un booléen
    return ['encryptedPrivateKey' => $encryptedPrivateKey, 'protectedKey' => $protectedKey->saveToAsciiSafeString()];
}

// Hash la clé publique pour l'identification externe.
function hashPublicKey($publicKey) {
    return hash('sha256', $publicKey->getPoint()->getX() . $publicKey->getPoint()->getY());
}

$walletExists = $db->querySingle("SELECT COUNT(*) as count FROM wallet") > 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['createWallet']) && !$walletExists && !empty($_POST['password'])) {
    $seedPhrase = generateSeedPhrase(__DIR__ . '/data/wordlist.txt', 12);
    $masterKey = generateMasterKey($seedPhrase);
    $keyDetails = encryptPrivateKey($masterKey, $_POST['password']);
    $publicKeyHashed = hashPublicKey($masterKey->getPublicKey());

    // Insertion des détails du portefeuille dans la base de données.
    $stmt = $db->prepare("INSERT INTO wallet (public_key, private_key_encrypted, encryption_key) VALUES (:publicKey, :privateKeyEncrypted, :encryptionKey)");
    $stmt->bindValue(':publicKey', $publicKeyHashed);
    $stmt->bindValue(':privateKeyEncrypted', $keyDetails['encryptedPrivateKey']);
    $stmt->bindValue(':encryptionKey', $keyDetails['protectedKey']);
    $stmt->execute();

    $seedPhraseDisplayed = $seedPhrase; // Préparation de la seed phrase pour l'affichage.
} elseif ($walletExists) {
    $publicKeyHashed = $db->querySingle("SELECT public_key FROM wallet LIMIT 1");
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
        <?php if ($walletExists): ?>
            <a href="mining.php">Mining</a>
        <?php endif; ?>
    </header>
    <div class="container">
        <?php if (!$walletExists): ?>
            <h2>Create your CoinCraft wallet</h2>
            <form action="index.php" method="post">
                <input type="password" name="password" placeholder="Choose a secure password" required>
                <button type="submit" name="createWallet">Create the wallet</button>
            </form>
            <?php
            if (isset($seedPhraseDisplayed)): ?>
                <p>Please securely note your Seed Phrase:<br/><b> <?= htmlspecialchars($seedPhraseDisplayed); ?></b></p>
                <p>Your CoinCraft wallet is ready. You can now start mining or making transactions.</p>
                <p>Wallet Address: <?= htmlspecialchars($publicKeyHashed); ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p>Your CoinCraft wallet is ready. You can now start mining or making transactions.</p>
            <p>Wallet Address: <?= htmlspecialchars($publicKeyHashed); ?></p>
        <?php endif; ?>
    </div>
</body>
</html>

