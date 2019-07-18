<?php
// This file installs the template. There's no need to change anything here. The file also deletes itself after use.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $SafeVars = [];
    $ExpectedVars = [
        'sqluser' => true,
        'sqlpass' => true,
        'sqldatabase' => true,
        'sxapikey' => true
    ];
    
    // Loop through our expected variables and make sure they're set and don't contain anything weird
    foreach($ExpectedVars as $Key => $Item){
        if(!isset($_POST[$Key]) || empty($_POST[$Key])){
            die(json_encode([
                'success' => false,
                'error' => 'missing_arguments',
                'msg' => $Key
            ]));
        }

        $Var = $_POST[$Key];

        $SafeVars[$Key] = $Var;
    }

    // Create database connection
    try {
        $DBC = new PDO('mysql:host=127.0.0.1;dbname='.$SafeVars['sqldatabase'].';port=3306;charset=utf8mb4', $SafeVars['sqluser'], $SafeVars['sqlpass']);
        $DBC->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        die(json_encode([
            'success' => false,
            'error' => 'sqlconn_failed',
            'msg' => $e->getMessage()
        ])); 
    }

    // Download and unzip template from github
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://codeload.github.com/SaimorIVS/Stavox-Tablet-App-Boilerplate/zip/master');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec ($curl);
    curl_close ($curl);

    if(!$data){
        die(json_encode([
            'success' => false,
            'error' => 'download_failed',
        ])); 
    }

    $destination = 'master.zip';
    $file = fopen($destination, 'w+');
    fputs($file, $data);
    fclose($file);

    $zip = new ZipArchive;
    
    $res = $zip->open($destination);
    if ($res !== TRUE) {
        die(json_encode([
            'success' => false,
            'error' => 'zip_open_failed',
            'msg' => 'code '.$res
        ])); 
    }

    $res = $zip->extractTo('.');
    if ($res != TRUE) {
        die(json_encode([
            'success' => false,
            'error' => 'unzip_failed',
        ])); 
    }

    $zip->close();
    unlink($destination);
    unlink('Stavox-Tablet-App-Boilerplate-master/install.php');

    // Delete self
    unlink(__FILE__);

    // Send response
    echo json_encode([
        'success' => true,
    ]);

    exit;
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    ?>
    <head>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    </head>
    <body class="d-flex flex-column">
        <main role="main" class="flex-shrink-0">
            <div class="container">
                <h1 class="mt-5">Boilerplate installation</h1>
                <p>Read <a target="_blank" href="https://github.com/SaimorIVS/Stavox-Tablet-App-Boilerplate/wiki">the wiki</a> for more info.</p>
                <div id="error" class="alert alert-danger d-none" role="alert"></div>
                <form id="infoform">
                    <div class="form-group">
                        <label>MySQL username</label>
                        <input type="text" class="form-control" placeholder="Enter MySQL username" name="sqluser" required>
                    </div>
                    <div class="form-group">
                        <label>MySQL database</label>
                        <input type="text" class="form-control" placeholder="Enter MySQL database" name="sqldatabase" required>
                    </div>
                    <div class="form-group">
                        <label>MySQL password</label>
                        <input type="password" class="form-control" placeholder="Enter MySQL password" name="sqlpass" required>
                    </div>
                    <div class="form-group">
                        <label>Stavox API key</label>
                        <input type="password" class="form-control" placeholder="Enter Stavox API key" name="sxapikey" required>
                    </div>
                    <button id="installbutton" type="submit" class="btn btn-primary btn-lg w-100">Install!</button>
                </form>
            </div>
        </main>
        <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
        <script>
            var installable = true
            var errors = {
                missing_arguments: 'Missing argument',
                invalid_argument: 'Invalid argument',
                sqlconn_failed: 'Could not connect to database',
                unzip_failed: 'Could not unzip template archive',
                download_failed: 'Could not download template archive',
                zip_open_failed: 'Could not open zip archive'
            }

            $('#infoform').submit(e => {
                if(!installable){
                    return
                }
                installable = false
                $('#installbutton').text('Installing...')
                e.preventDefault()
                $.post('', $('#infoform').serialize(), data => {
                    data = JSON.parse(data)
                    if(!data.success){
                        $('#installbutton').text('Install!')
                        $('#error').removeClass('d-none')

                        if(data.msg){
                            $('#error').html('<b>Error: </b>'+errors[data.error]+': '+data.msg)
                            return
                        }

                        $('#error').html('<b>Error: </b>'+errors[data.error])
                        return
                    }

                    $('body').empty()
                    $('body').addClass('align-items-center justify-content-center text-center')
                    $('body').html('<h1 class="display-4">Installed successfully!</h1><p>Click <a href="/">here</a> to go to the apps index page</p>')
                })
            })
        </script>
    </body>
    <?php
} else {
    die('Invalid method');
}