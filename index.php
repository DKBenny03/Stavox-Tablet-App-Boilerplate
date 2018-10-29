<?php

require('__app.php');

$App = new App();

$App->DoLogin();

$App->echoHead();

$App->echoNav();

$UserData = $App->getServerUserData();

?>

<h1>Hej <?php echo $UserData['Name'] ?></h1>

<p>Denne seje app er kun til at vise dig hvordan du kan lave din egen app. Den kan intet sejt.<br>Source koden er på min Github profil. Find den her: <b>https://github.com/emoyly/Stavox-Tablet-App-Template</b></p>

<!--
    Her laver vi bare lige en flot knap man kan klikke på, som der sender en moneyrequest.
    Moneyrequests oprettes med javascript funktionen sx.moneyRequest(POSTURL, TOKEN, AMOUNT)
    POSTURL er den url som serveren skal sende en post request til, når den har trukket pengene. Tjek _moneypost.php for mere information om dette.
    TOKEN er en unik token du kan generere, så du kan genkende requsten igen, når brugeren har betalt. Tjek _moneypost.php for mere information om dette.
    AMOUNT er mængden af penge du gerne vil efterspørge. Beløb under 100 vil ikke rent faktisk blive tilføjet til din konto, men du kan dog stadig modtage dem.
-->
<a id="moneybutton" style="cursor:pointer;" onclick="sx.moneyRequest('https://testapp_tabletapps.stavox.net/_moneypost.php', 'min unikke token lol', 10000000)">CLICK HERE 2 GIVE ME MONEYZ</a>


<!--
    Dette lille stykke javascript registerer funktionen sx_MoneyRequestAccept(), som automatisk bliver kørt på klienten, når din app har modtaget post requesten med penge fra serveren. Tjek _moneypost.php for mere information om dette.
    I dette eksempel, ændrer scriptet bare pengeknappens tekst til "thx 4 money"
-->
<script>
    function sx_MoneyRequestAccept(){
        document.getElementById('moneybutton').innerHTML = 'Thx 4 money'
    }
</script>
