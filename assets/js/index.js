// Whenever someone clicks the thingy with the ID of purchase we run this code
$('#purchase').click(e => {
    sx.moneyRequest('https://'+window.location.host+'/webhooks/paymentcompleted.php', 'MyPaymentID', 10000)
})

// This function is executed when the money has been taken from the player.
// It is executed by the client though, so do not trust it. Use it for reloading a page or something. Never trust the client.
function sx_MoneyRequestAccept(){
    $('#purchase').text('Thx 4 moneyz')
}

// Whenever someone clicks the thingy with the ID of notification we run this code
$('#notification').click(e => {
    // Send post request to our api
    $.post('/api/sendnotification.php', {}, data => {
        // Update button text
        $('#notification').text('Notification sent!')
    })
})
