<?php

namespace CroinCraft\Blockchain;

use SQLite3;
use Exception;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\KeyProtectedByPassword;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Crypto\Key\PrivateKeyInterface;

class Transaction {
    private $db;

    public function __construct($dbPath) {
        // Connexion à la base de données SQLite pour le mempool
        $this->db = new SQLite3($dbPath);
        // Création de la table `mempool` si elle n'existe pas déjà
        $this->initializeDB();
    }

    private function initializeDB() {
        // SQL pour créer la table `mempool` si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS mempool (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            senderPublicKey TEXT NOT NULL,
            recipientPublicKey TEXT NOT NULL,
            amount REAL NOT NULL,
            signature TEXT NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(senderPublicKey, recipientPublicKey, timestamp)
        );

        CREATE INDEX IF NOT EXISTS idx_sender ON mempool(senderPublicKey);
        CREATE INDEX IF NOT EXISTS idx_recipient ON mempool(recipientPublicKey);
        ";
        $this->db->exec($sql);
    }

    public function createTransaction($amount, $recipientPublicKey, $walletPassword, $senderPublicKey) {
        // Récupère et décrypte la clé privée de l'expéditeur avec le mot de passe fourni
        $privateKey = $this->getPrivateKey($walletPassword, $senderPublicKey);
        
        // Valide la transaction (cette étape est simplifiée pour l'exemple)
        $isValid = $this->validateTransaction($amount, $recipientPublicKey, $privateKey);
        if (!$isValid) {
            throw new Exception("Invalid transaction.");
        }
        
        // Insère la transaction dans la base de données `mempool`
        $this->insertTransaction($senderPublicKey, $recipientPublicKey, $amount, $privateKey);
    }

    private function getPrivateKey($walletPassword, $senderPublicKey) {
        // Préparer la requête SQL pour récupérer la clé privée chiffrée et la clé de chiffrement pour la clé publique donnée
        $stmt = $this->db->prepare("SELECT private_key_encrypted, encryption_key FROM wallet WHERE public_key = :publicKey");
        $stmt->bindValue(':publicKey', $senderPublicKey, SQLITE3_TEXT);
        $result = $stmt->execute();
        
        if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            try {
                // Déchiffrer la clé privée en utilisant le mot de passe fourni et la clé de chiffrement stockée
                $encryptionKey = Key::loadFromAsciiSafeString($row['encryption_key']);
                $decryptedPrivateKey = Crypto::decryptWithPassword($row['private_key_encrypted'], $walletPassword, true, $encryptionKey);
                
                // Convertir la clé privée déchiffrée en objet PrivateKey compatible ECC
                // Cette conversion suppose que le format de la clé privée déchiffrée est directement utilisable
                $generator = EccFactory::getSecgCurves()->generator256k1();
                $privateKey = $generator->getPrivateKeyFrom(gmp_init($decryptedPrivateKey, 16));

                return $privateKey;
            } catch (Exception $e) {
                throw new Exception("Échec du déchiffrement ou du traitement de la clé privée : " . $e->getMessage());
            }
        } else {
            throw new Exception("Portefeuille avec la clé publique fournie non trouvé.");
        }
    }

    private function validateTransaction($amount, $recipientPublicKey, $privateKey) {
        // Validez ici la transaction avec la clé privée.
        // Cette fonction renvoie true pour simplifier l'exemple.
        return true;
    }

    private function insertTransaction($senderPublicKey, $recipientPublicKey, $amount, $privateKey) {
        // Prépare la requête SQL pour insérer la transaction dans la base de données `mempool`
        $stmt = $this->db->prepare("INSERT INTO mempool (senderPublicKey, recipientPublicKey, amount, signature) VALUES (?, ?, ?, ?)");
        // Ici, la signature est simulée. Remplacez-la par la signature réelle générée à partir de la clé privée.
        $signature = "simulated_signature"; // À remplacer par votre logique de signature
        $stmt->bindValue(1, $senderPublicKey, SQLITE3_TEXT);
        $stmt->bindValue(2, $recipientPublicKey, SQLITE3_TEXT);
        $stmt->bindValue(3, $amount, SQLITE3_FLOAT);
        $stmt->bindValue(4, $signature, SQLITE3_TEXT);
        $stmt->execute();
    }
}
