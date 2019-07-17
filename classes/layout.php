<?php
// This class contains all the things we're gonna be using in our HTML more than once. Basic things like the header, navbar and footer.
// You can, of course, remove any of these, if you do not need them
class Layout
{
    use Singleton;

    public function header()
    {
        ?>
        <head>
            <link rel="stylesheet" type="text/css" href="/vendor/bootstrap-4.3.1/bootstrap.min.css">

            <!-- Adding the Config::i()->getVersion() thing makes caching way easier to deal with. In development mode, the version will be a randomized string on each visit, to completely bypass the cache -->
            <link rel="stylesheet" type="text/css" href="/assets/css/app.css?v=<?=Config::i()->getVersion()?>">
        </head>
        <?php
    }

    public function nav()
    {
        ?>
        <nav class="navbar navbar-expand-md navbar-dark bg-dark mb-4">
            <a class="navbar-brand" href="/">My cool app</a>

            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/">Home</a>
                </li>
            </ul>
        </nav>
        <?php
    }

    public function footer()
    {
        ?>
            <footer class="mt-auto text-center text-muted">
                <!-- Didn't know what to add in this footer so whatever -->
                <p>© Incompetent asshole 2019</p>
            </footer>
            <script src="/vendor/jquery-3.4.1/jquery.min.js"></script>
            <script src="/vendor/popper-1.14.7/popper.min.js"></script>
            <script src="/vendor/bootstrap-4.3.1-min.js"></script>
        <?php
    }

    public function error($Title, $Message = null)
    {
        echo '<head></head>';
        echo '<body style="text-align: center;">';

        echo '<h1>'.$Title.'</h1>';
        echo '<p>'.(isset($Message) ? $Message : 'No message provided').'</p>';

        echo '</body>';
    }
}