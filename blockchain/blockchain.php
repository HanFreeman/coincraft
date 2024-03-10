<?php

require_once 'block.php'; // Inclut la définition de la classe Block.

class Blockchain {
    private $dataBlockPath; // Chemin vers le dossier où les blocs seront stockés.

    public function __construct() {
        // Définit le chemin vers le dossier 'datablock', relatif au répertoire parent de ce script.
        $this->dataBlockPath = dirname(__DIR__) . '/datablock/';
        // Initialise la blockchain avec le bloc Genesis si nécessaire.
        $this->initializeChain();
    }

    private function initializeChain() {
        // Compte le nombre de fichiers dans le dossier 'datablock', excluant '.' et '..'.
        if (count(scandir($this->dataBlockPath)) == 2) {
            // S'il n'y a pas d'autres fichiers, crée le bloc Genesis.
            $genesisBlock = new Block(0, 'Genesis Block', '0');
            // Ajoute le bloc Genesis à la blockchain.
            $this->addBlock($genesisBlock);
        }
    }

    public function addBlock($block) {
        $lastFileName = $this->getLastFileName(); // Obtient le nom du dernier fichier de données.
        $filePath = $this->dataBlockPath . $lastFileName; // Construit le chemin complet vers ce fichier.

        // Vérifie si le fichier actuel dépasse la taille maximale après l'ajout du nouveau bloc.
        if ($this->willExceedMaxSize($filePath, $block)) {
            // Si oui, crée un nouveau fichier de données.
            $filePath = $this->createNewDataFile();
        }

        // Lit les blocs existants, s'ils existent, à partir du fichier actuel.
        $blocks = file_exists($filePath) && filesize($filePath) > 0 ? unserialize(gzuncompress(file_get_contents($filePath))) : [];

        // Détermine l'index et le previousHash du nouveau bloc.
        if (!empty($blocks)) {
            $lastBlock = end($blocks);
            $block->index = $lastBlock->index + 1; // Incrémente l'index par rapport au dernier bloc.
            $block->previousHash = $lastBlock->hash; // Utilise le hash du dernier bloc comme previousHash.
        } else {
            // Pour le bloc Genesis ou le premier bloc d'un nouveau fichier.
            $block->index = count($blocks);
            $block->previousHash = '0';
        }

        $blocks[] = $block; // Ajoute le nouveau bloc au tableau des blocs.

        // Sérialise et compresse le tableau de blocs avant de l'écrire dans le fichier.
        file_put_contents($filePath, gzcompress(serialize($blocks)));
    }

    private function willExceedMaxSize($filePath, $block) {
        if (!file_exists($filePath)) {
            return false; // Si le fichier n'existe pas, il ne peut pas dépasser la taille maximale.
        }

        $currentFileSize = filesize($filePath); // Obtient la taille actuelle du fichier.
        $blockSize = strlen(gzcompress(serialize([$block]))); // Estime la taille du bloc ajouté.
        return ($currentFileSize + $blockSize) > 134217728; // Vérifie si l'ajout dépasse 128 Mo.
    }

    private function createNewDataFile() {
        $files = scandir($this->dataBlockPath); // Liste tous les fichiers dans 'datablock'.
        $blockFiles = array_filter($files, function ($file) { // Filtre les fichiers de blocs.
            return strpos($file, 'block_') !== false;
        });

        $newFileName = 'block_' . (count($blockFiles) + 1) . '.dat'; // Nomme le nouveau fichier.
        $newFilePath = $this->dataBlockPath . $newFileName; // Construit le chemin complet.
        file_put_contents($newFilePath, gzcompress(serialize([]))); // Crée un fichier avec un tableau vide sérialisé et compressé.
        return $newFileName; // Retourne le nom du nouveau fichier.
    }

    public function getLastFileName() {
        $files = array_diff(scandir($this->dataBlockPath), array('.', '..')); // Liste les fichiers, excluant '.' et '..'.
        $blockFiles = array_filter($files, function ($file) {
            return strpos($file, 'block_') !== false;
        });

        if (empty($blockFiles)) {
            return $this->createNewDataFile(); // Crée un nouveau fichier s'il n'y a pas de fichiers de blocs.
        } else {
            natsort($blockFiles); // Trie les fichiers pour obtenir le dernier.
            return end($blockFiles); // Retourne le nom du dernier fichier de blocs.
        }
    }

    public function getDataBlockPath() {
        return $this->dataBlockPath; // Retourne le chemin du dossier 'datablock'.
    }
}

?>
