<?php

// Déclaration de la classe Block
class block
{

    public $index; // L'index du bloc dans la chaîne
    public $timestamp; // Le horodatage de la création du bloc
    public $data; // Les données contenues dans le bloc, peut être n'importe quelle donnée
    public $previousHash; // Le hash du bloc précédent dans la chaîne, permet de lier les blocs entre eux
    public $hash; // Le hash du bloc actuel, calculé à partir de ses propriétés
    
    // Constructeur de la classe Block, appelé lors de la création d'un nouveau bloc
    public function __construct($index, $data, $previousHash = '')
    {
    
        $this->index = $index; // Initialisation de l'index avec la valeur fournie
        $this->timestamp = time(); // Initialisation du horodatage
        $this->data = $data; // Initialisation des données avec la valeur fournie
        $this->previousHash = $previousHash; // Initialisation du hash précédent avec la valeur fournie
        $this->hash = $this->calculateHash(); // Calcul du hash du bloc lors de sa création
    
    }
    
    // Méthode pour calculer le hash du bloc
    public function calculateHash()
    {
    
        // Utilisation de la fonction hash de PHP pour calculer un hash SHA-256 en fonction de l'index, du horodatage, des données et du hash précédent du bloc
        return hash('sha256', $this->index . $this->timestamp . $this->data . $this->previousHash);
    
    }

}

?>
