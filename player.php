<?php
// This is the index/front page of your app. This is what will be shown to the user upon startup

// Requires our autoloading and classes
require_once '__init.php';

// Handle user sign-in
User::i()->login();

// Echo the <head> into our document
Layout::i()->header();

if(User::i()->getGangID()){
    $Gang = SxApi::i()->getGang(User::i()->getGangID());

    if(!$Gang['success']){
        echo 'Error getting gang data: '.$Gang['error'];
        exit;
    }
}

?>
<body>

<?php
// Echo our navbar
Layout::i()->nav();
?>

<div class="text-center">
    <h1 class="display-4">Welcome <?=User::i()->getName()?></h1>
    <p class="lead"><?=User::i()->getGangID() ? 'Gang: '.$Gang['Name'] : 'You are not in a gang' ?></p>
</div>

<?php
// Echo our footer and scripts
Layout::i()->footer();
?>

<!-- Remember to close the body again! -->
</body>