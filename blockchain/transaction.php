<?php

namespace CroinCraft\Blockchain;

use DateTime;
use SQLite3;
use Exception;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Crypto\Signature\Signer;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

// Classe Transaction définissant la structure et le comportement d'une transaction dans la blockchain CroinCraft.
class Transaction {
    public $inputs;         // Références aux sorties des transactions précédentes
    public $outputs;        // Destinataires et montants des sorties de la transaction
    public $version;        // Version de la transaction, pour compatibilité future
    public $locktime;       // Temps ou numéro de bloc à partir duquel la transaction est valide
    public $signature;      // Signature numérique de la transaction
    public $timestamp;      // Horodatage de la transaction

    // Constructeur pour initialiser une transaction avec ses propriétés de base
    public function __construct($inputs, $outputs, $version = 1, $locktime = 0) {
        $this->inputs = $inputs;
        $this->outputs = $outputs;
        $this->version = $version;
        $this->locktime = $locktime;
        $this->timestamp = (new DateTime())->getTimestamp();
        $this->signature = ''; // Sera généré lors de la signature de la transaction
    }

    // Fonction pour signer la transaction à l'aide de la clé privée du créateur
    public function signTransaction($publicKey, $password) {
        $dbPath = __DIR__ . '/../data/wallet.db'; // Chemin vers la base de données des portefeuilles
        $db = new SQLite3($dbPath);

        // Requête pour récupérer la clé privée chiffrée et la clé de chiffrement associée
        $query = $db->prepare("SELECT private_key_encrypted, encryption_key FROM wallet WHERE public_key = :publicKey");
        $query->bindValue(':publicKey', $publicKey);
        $result = $query->execute();

        if ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            try {
                // Déchiffrement de la clé privée avec le mot de passe de l'utilisateur
                $encryptionKey = Key::loadFromAsciiSafeString($row['encryption_key']);
                $privateKeyEncrypted = $row['private_key_encrypted'];
                $privateKeySerialized = Crypto::decryptWithPassword($privateKeyEncrypted, $password, $encryptionKey);

                // Récupération de l'objet PrivateKey à partir de sa forme sérialisée
                $adapter = EccFactory::getAdapter();
                $generator = EccFactory::getSecgCurves()->generator256k1();
                $privateKey = $generator->createPrivateKeyFrom(gmp_strval(gmp_init(bin2hex($privateKeySerialized), 16)));

                // Préparation des données de la transaction à signer
                $dataToSign = $this->getTransactionData();

                // Signature de la transaction
                $signer = new Signer($adapter);
                $hash = hash('sha256', $dataToSign, true);
                $signature = $signer->sign($privateKey, gmp_init(bin2hex($hash), 16));

                // Stockage de la signature dans une forme exploitable
                $this->signature = bin2hex($signature->getR()) . bin2hex($signature->getS());
            } catch (Exception $e) {
                throw new Exception("Erreur lors de la signature de la transaction : " . $e->getMessage());
            }
        } else {
            throw new Exception("Portefeuille non trouvé.");
        }
    }

    // Compilation des données de la transaction pour la signature ou d'autres usages
    private function getTransactionData() {
        return json_encode([
            'inputs' => $this->inputs,
            'outputs' => $this->outputs,
            'version' => $this->version,
            'locktime' => $this->locktime,
            'timestamp' => $this->timestamp
        ]);
    }

    // Validation de la transaction avant son ajout à un bloc ou au mempool
    public function validateTransaction() {
        // La logique de validation de la transaction devrait être ici
        return true; // Ici, on simule une validation réussie pour l'exemple
    }

    // Enregistrement de la transaction dans le mempool une fois validée
    public function saveToMempool($dbPath) {
        $db = new SQLite3($dbPath);

        // Crée la table mempool si nécessaire
        $db->exec("CREATE TABLE IF NOT EXISTS mempool (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            inputs TEXT,
            outputs TEXT,
            version INTEGER,
            locktime INTEGER,
            signature TEXT,
            timestamp INTEGER
        )");

        // Insertion de la transaction dans le mempool
        $stmt = $db->prepare("INSERT INTO mempool (inputs, outputs, version, locktime, signature, timestamp) VALUES (:inputs, :outputs, :version, :locktime, :signature, :timestamp)");
        $stmt->bindValue(':inputs', json_encode($this->inputs));
        $stmt->bindValue(':outputs', json_encode($this->outputs));
        $stmt->bindValue(':version', $this->version);
        $stmt->bindValue(':locktime', $this->locktime);
        $stmt->bindValue(':signature', $this->signature);
        $stmt->bindValue(':timestamp', $this->timestamp);

        $stmt->execute();
    }
}
?>
