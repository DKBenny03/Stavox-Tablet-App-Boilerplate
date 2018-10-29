<?php

require('__app.php');

$App = new App();

// -- Serveren sender følgende med i hver post request:
// SteamID => Hvem end der har sendt pengene
// Amount => Mængden af penge. Et mindre gebyr bliver taget fra overførslen, men denne returnerer fulde beløb, så du kan bekræfte at det matcher.
// Token => En unik token du registerer, så du kan genkende den specifikke betaling. Husk stadig at tjekke beløbets mængde, da tokenen stadig kan blive sendt, selv hvis at beløbet ikke er hvad du har efterspurgt.
// YourAPIKey => Din apps api nøgle, så du kan bekræfte at requesten kommer fra den rigtige kilde

if($_POST['YourAPIKey']!=$App->getApiKey()){ // Tjekker om apinøglen matcher din apps apinøgle. Hvis ikke, så exitter vi bare
    exit;
}

// Denne app indsætter bare dataen i en database.

$stmt = $App->getConnection()->prepare('INSERT INTO moneylog (SteamID, Token, Amount) VALUES (:SteamID, :Token, :Amount)');

$stmt->bindParam(':SteamID', $_POST['SteamID']);
$stmt->bindParam(':Amount', $_POST['Amount']);
$stmt->bindParam(':Token', $_POST['Token']);

$stmt->execute();

?>