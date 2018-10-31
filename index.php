<?php

require('__app.php');

$App = new App();

$App->DoLogin();

$App->echoHead();

$App->echoNav();

$UserData = $App->getServerUserData();

?>

<main role="main" class="container">

    <div class="starter-template">
        <h1 class="display-4">Hej <?php echo $UserData['Name'] ?></h1>

        <p class="lead">Denne seje app er kun til at vise dig hvordan du kan lave din egen app. Den kan intet sejt.<br>Source koden er på min Github profil. Find den her: <b>https://github.com/emoyly/Stavox-Tablet-App-Template</b></p>

        <button id="moneybutton" class="btn btn-primary">MoneyRequest</button>

    </div>

</main><!-- /.container -->



<?php
    $App->echoFooter();
?>