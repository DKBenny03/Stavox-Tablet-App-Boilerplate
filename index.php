<?php
// This is the index/front page of your app. This is what will be shown to the user upon startup

// Requires our autoloading and classes
require_once '__init.php';

// Handle user sign-in
User::i()->login();

// Echo the <head> into our document
Layout::i()->header();

?>
<body>

<?php
// Echo our navbar
Layout::i()->nav();
?>

<div class="text-center">
    <h1 class="display-4">This is my cool app.</h1>
    <p class="lead">With my cool app you can give me money and let me spam you with notifications 😄</p>
    <button id="purchase" class="btn btn-primary">Send moneys</button>
    <button id="notification" class="btn btn-warning">Send notification</button>
    <button id="expression" class="btn btn-danger">Open Expression2</button>
    <button id="dupe" class="btn btn-info">Save dupe</button>
    <button id="givemoney" class="btn btn-secondary">Get 100 moneys from app</button>
    <a href="/player.php" class="btn btn-primary">View my player data</a>
    <!-- Set tablet closeable checkbox -->
    <div class="custom-control custom-checkbox mt-2">
        <input type="checkbox" class="custom-control-input" id="tabletcloseable" checked>
        <label class="custom-control-label" for="tabletcloseable">Allow tablet to be closed</label>
    </div>
</div>

<?php
// Echo our footer and scripts
Layout::i()->footer();
?>

<!-- Adding the Config::i()->getVersion() thing makes caching way easier to deal with. In development mode, the version will be a randomized string on each visit, to completely bypass the cache -->
<script src="/assets/js/index.js?v=<?=Config::i()->getVersion()?>"></script>

<!-- Remember to close the body again! -->
</body>