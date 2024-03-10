<?php
// Inclusion des classes nécessaires depuis le dossier blockchain
require_once 'blockchain/block.php';
require_once 'blockchain/blockchain.php';

// Instanciation de la classe Blockchain
$blockchain = new Blockchain();

// Vérifie si le formulaire a été soumis avec des données
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['blockData'])) {
    // Récupère les données du formulaire
    $blockData = $_POST['blockData'];

    // Crée un nouveau bloc avec les données fournies
    $newBlock = new Block(0, $blockData, ''); // L'index et le previousHash seront ajustés dans addBlock

    // Ajoute le nouveau bloc à la blockchain
    $blockchain->addBlock($newBlock);
}

// Fonction pour afficher la blockchain
function displayBlockchain($blockchain) {
    $files = array_diff(scandir($blockchain->getDataBlockPath()), array('.', '..')); // Ignore les entrées de répertoire . et ..
    if (empty($files)) {
        echo "<p>Aucun bloc disponible dans la blockchain.</p>"; // Affiche un message si la blockchain est vide
        return;
    }

    rsort($files); // Trie les fichiers par ordre décroissant

    foreach ($files as $file) {
        $blocksData = gzuncompress(file_get_contents($blockchain->getDataBlockPath() . $file)); // Décompresse les données du fichier
        $blocks = unserialize($blocksData); // Désérialise les données pour obtenir le tableau des blocs

        if (is_array($blocks)) {
            $blocks = array_reverse($blocks); // Inverse l'ordre des blocs pour afficher du plus récent au plus ancien
            foreach ($blocks as $block) {
                // Affiche les informations de chaque bloc
                echo "Index: " . $block->index . "<br>";
                echo "Timestamp: " . date('Y-m-d H:i:s', $block->timestamp) . "<br>";
                echo "Data: " . htmlspecialchars($block->data) . "<br>"; // Utilise htmlspecialchars pour éviter les injections XSS
                echo "Previous Hash: " . $block->previousHash . "<br>";
                echo "Hash: " . $block->hash . "<br><br>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>CoinCraft Core</title>
</head>
<body>
    <header class="container">
        <img src="images/logo_bannier2.png" alt="Logo CoinCraft">
        <a href="index.php">Wallet</a>       
    </header>
    <div class="container">
      <h1>Add a new block</h1>
      <form action="mining.php" method="post">
          <label for="blockData">Block data :</label><br>
          <input type="text" id="blockData" name="blockData" required><br><br>
          <input type="submit" value="Add the block">
      </form>
    </div>
    <div class="container">
    <h2>Coincraftscan</h2>
    <?php
    // Affiche la blockchain
    displayBlockchain($blockchain);
    ?>
    </div>
</body>
</html>
