<?php

namespace CroinCraft\Blockchain;

use DateTime;
use SQLite3;

class Transaction {
    public $inputs;         // Entrées de la transaction (références aux sorties précédentes)
    public $outputs;        // Sorties de la transaction (destinataires et montants)
    public $amount;         // Montant total de la crypto-monnaie à transférer
    public $transactionFee; // Frais de transaction
    public $signature;      // Signature numérique pour authentifier la transaction
    public $timestamp;      // Horodatage de la transaction

    // Constructeur pour initialiser une nouvelle transaction
    public function __construct($inputs, $outputs, $amount, $transactionFee) {
        $this->inputs = $inputs;
        $this->outputs = $outputs;
        $this->amount = $amount;
        $this->transactionFee = $transactionFee;
        $this->timestamp = (new DateTime())->getTimestamp(); // Générer l'horodatage actuel
        $this->signature = ''; // Sera défini après la signature de la transaction
    }

    // Fonction pour signer la transaction avec la clé privée
    public function signTransaction($privateKey) {
        // La logique pour signer la transaction en utilisant votre bibliothèque de cryptographie
        $dataToSign = $this->getTransactionData(); // Préparer les données à signer
        $this->signature = "signature_placeholder"; // Simuler une signature pour l'exemple
    }

    // Fonction pour rassembler les données de la transaction en vue de leur signature ou d'autres utilisations
    private function getTransactionData() {
        return json_encode([
            'inputs' => $this->inputs,
            'outputs' => $this->outputs,
            'amount' => $this->amount,
            'transactionFee' => $this->transactionFee,
            'timestamp' => $this->timestamp
        ]);
    }

    // Fonction pour valider la transaction avant son ajout au bloc
    public function validateTransaction() {
        // La logique de validation de la transaction, y compris la vérification de la signature
        return true; // Simuler une transaction valide pour l'exemple
    }

    // Méthode pour sauvegarder la transaction dans le mempool
    public function saveToMempool($dbPath) {
        $db = new SQLite3($dbPath);
        
        // Création de la table mempool si elle n'existe pas déjà
        $db->exec("CREATE TABLE IF NOT EXISTS mempool (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            inputs TEXT,
            outputs TEXT,
            amount REAL,
            transactionFee REAL,
            signature TEXT,
            timestamp INTEGER
        )");

        // Préparation de la requête pour insérer la transaction dans le mempool
        $stmt = $db->prepare("INSERT INTO mempool (inputs, outputs, amount, transactionFee, signature, timestamp) VALUES (:inputs, :outputs, :amount, :transactionFee, :signature, :timestamp)");

        // Liaison des paramètres
        $stmt->bindValue(':inputs', json_encode($this->inputs));
        $stmt->bindValue(':outputs', json_encode($this->outputs));
        $stmt->bindValue(':amount', $this->amount);
        $stmt->bindValue(':transactionFee', $this->transactionFee);
        $stmt->bindValue(':signature', $this->signature);
        $stmt->bindValue(':timestamp', $this->timestamp);

        // Exécution de la requête
        $stmt->execute();
    }
}
