/*
    Her laver vi funktionen til vores moneyrequest knap.
    Moneyrequests oprettes med javascript funktionen sx.moneyRequest(POSTURL, TOKEN, AMOUNT)
    POSTURL er den url som serveren skal sende en post request til, når den har trukket pengene. Tjek _moneypost.php for mere information om dette.
    TOKEN er en unik token du kan generere, så du kan genkende requsten igen, når brugeren har betalt. Tjek _moneypost.php for mere information om dette.
    AMOUNT er mængden af penge du gerne vil efterspørge. Beløb under 100 vil ikke rent faktisk blive tilføjet til din konto, men du kan dog stadig modtage dem.
*/
$("#moneybutton").click(function () {
    sx.moneyRequest('https://testapp_tabletapps.stavox.net/_moneypost.php', 'Betaling', 1000)
});


/* 
    Dette lille stykke kode registerer funktionen sx_MoneyRequestAccept(), som automatisk bliver kørt på klienten, når din app har modtaget post requesten med penge fra serveren. Tjek _moneypost.php for mere information om dette.
    I dette eksempel, ændrer scriptet bare pengeknappens tekst til "thx 4 money"
*/
function sx_MoneyRequestAccept() {
    document.getElementById('moneybutton').innerHTML = 'Thx 4 money'
}