<?php

namespace CroinCraft\Blockchain;

use SQLite3;
use Exception;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\KeyProtectedByPassword;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Crypto\Key\PrivateKeyInterface;

class Wallet {
    private $db;

    public function __construct($dbPath) {
        // Connexion à la base de données SQLite
        $this->db = new SQLite3($dbPath);
        // Initialisation de la base de données (création de la table si elle n'existe pas)
        $this->initializeDB();
    }

    private function initializeDB() {
        // Crée la table 'wallet' si elle n'existe pas
        $this->db->exec("CREATE TABLE IF NOT EXISTS wallet (id INTEGER PRIMARY KEY, public_key TEXT, private_key_encrypted TEXT, encryption_key TEXT)");
    }

    public function walletExists() {
        // Vérifie si un portefeuille existe déjà
        $count = $this->db->querySingle("SELECT COUNT(*) as count FROM wallet");
        return $count > 0;
    }

    public function createWallet($password) {
        // Crée un nouveau portefeuille en utilisant le mot de passe fourni
        if ($this->walletExists()) {
            return ['error' => 'A wallet already exists.'];
        }

        try {
            // Génère une phrase de récupération et une clé maîtresse
            $seedPhrase = $this->generateSeedPhrase(__DIR__ . '/../data/wordlist.txt', 12);
            $masterKey = $this->generateMasterKey($seedPhrase);
            // Crypte la clé privée et hash la clé publique
            $keyDetails = $this->encryptPrivateKey($masterKey, $password);
            $publicKeyHashed = $this->hashPublicKey($masterKey->getPublicKey());

            // Insère le portefeuille dans la base de données
            $stmt = $this->db->prepare("INSERT INTO wallet (public_key, private_key_encrypted, encryption_key) VALUES (?, ?, ?)");
            $stmt->bindValue(1, $publicKeyHashed, SQLITE3_TEXT);
            $stmt->bindValue(2, $keyDetails['encryptedPrivateKey'], SQLITE3_TEXT);
            $stmt->bindValue(3, $keyDetails['protectedKey'], SQLITE3_TEXT);
            $stmt->execute();

            return ['seedPhrase' => $seedPhrase, 'publicKeyHashed' => $publicKeyHashed];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Ajout de la méthode getWalletPublicKey pour récupérer l'adresse publique du portefeuille
    public function getWalletPublicKey() {
        // Récupère l'adresse publique du portefeuille existant
        $publicKey = $this->db->querySingle("SELECT public_key FROM wallet LIMIT 1");
        return $publicKey ?: '';
    }

    // Les méthodes privées suivantes restent inchangées
    private function generateSeedPhrase($wordlistPath, $wordCount) {
        // Génère une phrase de récupération à partir d'une liste de mots
        $words = file($wordlistPath, FILE_IGNORE_NEW_LINES);
        if ($words === false) {
            throw new Exception("Unable to load the wordlist.");
        }
        $selectedWords = array_map(function ($i) use ($words) { return $words[$i]; }, array_rand($words, $wordCount));
        return implode(' ', $selectedWords);
    }

    private function generateMasterKey($seedPhrase) {
    // Convertit la seed phrase en hash binaire.
    $seedPhraseHash = hash('sha256', $seedPhrase, true);
    // Initialisation du générateur pour la courbe secp256k1.
    $generator = EccFactory::getSecgCurves()->generator256k1();
    // Convertit le hash binaire en entier GMP, puis en chaîne hexadécimale, et finalement en objet GMP.
    $privateKey = $generator->getPrivateKeyFrom(gmp_init(bin2hex($seedPhraseHash), 16));
    return $privateKey;
    }

    private function encryptPrivateKey(PrivateKeyInterface $privateKey, $password) {
    // Crypte la clé privée pour le stockage sécurisé
    $protectedKey = KeyProtectedByPassword::createRandomPasswordProtectedKey($password);
    $encryptedPrivateKey = Crypto::encryptWithPassword((string)$privateKey->getSecret(), $password, true);
    return ['encryptedPrivateKey' => $encryptedPrivateKey, 'protectedKey' => $protectedKey->saveToAsciiSafeString()];
    }

    private function hashPublicKey($publicKey) {
        // Hash la clé publique pour utilisation externe
        return hash('sha256', $publicKey->getPoint()->getX() . $publicKey->getPoint()->getY());
    }
}
?>
