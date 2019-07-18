<?php
// This file installs the template. There's no need to change anything here. The file also deletes itself after use.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $SafeVars = [];
    $ExpectedVars = [
        'sqluser' => true,
        'sqlpass' => true,
        'sqldatabase' => true,
        'sxapikey' => true,
    ];

    // Loop through our expected variables and make sure they're set and don't contain anything weird
    foreach ($ExpectedVars as $Key => $Item) {
        if (!isset($_POST[$Key]) || empty($_POST[$Key])) {
            die(json_encode([
                'success' => false,
                'error' => 'missing_arguments',
                'msg' => $Key,
            ]));
        }

        $Var = $_POST[$Key];

        $SafeVars[$Key] = $Var;
    }

    // Create database connection
    try {
        $DBC = new PDO('mysql:host=127.0.0.1;dbname=' . $SafeVars['sqldatabase'] . ';port=3306;charset=utf8mb4', $SafeVars['sqluser'], $SafeVars['sqlpass']);
        $DBC->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        die(json_encode([
            'success' => false,
            'error' => 'sqlconn_failed',
            'msg' => $e->getMessage(),
        ]));
    }

    try {
        $stmt = $DBC->query('CREATE TABLE IF NOT EXISTS users
        (
          steamid  VARCHAR(255)                       NOT NULL PRIMARY KEY,
          name     VARCHAR(255)                       NOT NULL COMMENT \'RP name\',
          `rank`   VARCHAR(255)                       NOT NULL COMMENT \'In-game rank (user, admin etc.)\',
          vip      TINYINT(1)      DEFAULT 0                 NOT NULL COMMENT \'VIP status\',
          lastseen DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL on update CURRENT_TIMESTAMP,
          created  DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL
        )
        COMMENT \'This table stores all the users of your app. Feel free to add more columns.\';');
    } catch (Exception $e) {
        die(json_encode([
            'success' => false,
            'error' => 'sqlinit_failed',
            'msg' => $e->getMessage(),
        ]));
    }

    // Download and unzip template from github
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, 'https://codeload.github.com/SaimorIVS/Stavox-Tablet-App-Boilerplate/zip/master');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    curl_close($curl);

    if (!$data) {
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
    if ($res !== true) {
        die(json_encode([
            'success' => false,
            'error' => 'zip_open_failed',
            'msg' => 'code ' . $res,
        ]));
    }

    $res = $zip->extractTo('.');
    if ($res != true) {
        die(json_encode([
            'success' => false,
            'error' => 'unzip_failed',
        ]));
    }

    $zip->close();

    $path = 'Stavox-Tablet-App-Boilerplate-master';

    $configfilepath = $path . '/classes/config.php';
    $res = rename($path . '/classes/config.dist.php', $configfilepath);
    if (!$res) {
        die(json_encode([
            'success' => false,
            'error' => 'config_rename_failed',
        ]));
    }

    // Update the config file
    $contents = file_get_contents($configfilepath);
    if (!$contents) {
        die(json_encode([
            'success' => false,
            'error' => 'config_read_failed',
        ]));
    }

    $replace = [
        'SQLUSER' => $SafeVars['sqluser'],
        'SQLPASS' => $SafeVars['sqlpass'],
        'SQLDATABASE' => $SafeVars['sqldatabase'],
        'SXAPIKEY' => $SafeVars['sxapikey'],
    ];

    foreach ($replace as $Key => $Item) {
        $contents = str_replace('%%' . $Key . '%%', $Item, $contents);
    }

    $res = file_put_contents($configfilepath, $contents);
    if (!$res) {
        die(json_encode([
            'success' => false,
            'error' => 'config_write_failed',
        ]));
    }

    // File deletion
    $DeleteBlacklist = [
        '.' => true,
        '..' => true,
        'Stavox-Tablet-App-Boilerplate-master' => true,
    ];
    $toDelete = scandir(__DIR___);

    array_push($DeleteBlacklist, $path . '/install.php');

    // Function to recursively delete folders
    function rrmdir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                $res = false;
                if (is_dir($dir . "/" . $object)) {
                    $res = rrmdir($dir . "/" . $object);
                } else {
                    $res = unlink($dir . "/" . $object);
                }

                if (!$res) {
                    return false;
                }
            }
        }
        return rmdir($dir);
    }

    foreach ($toDelete as $Key => $Item) {
        if (isset($DeleteBlacklist[$Item]) || !file_exists($Item)) {
            continue;
        }

        if (is_dir($Item)) {
            $res = rrmdir($Item);
        } else {
            $res = unlink($Item);
        }

        if (!$res) {
            die(json_encode([
                'success' => false,
                'error' => 'filedelete_failed',
                'msg' => $Item,
            ]));
        }
    }

    $dir = scandir($path);
    if (!$dir) {
        die(json_encode([
            'success' => false,
            'error' => 'dirscan_failed',
        ]));
    }

    // Move all files from the template into the root web folder
    foreach ($dir as $Key => $Item) {
        if ($Item == '..' || $Item == '.') {
            continue;
        }

        $res = rename($path . '/' . $Item, $Item);
        if (!$res) {
            die(json_encode([
                'success' => false,
                'error' => 'move_failed',
                'msg' => $Item,
            ]));
        }
    }

    // Delete template folder
    unlink($path);

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
                zip_open_failed: 'Could not open zip archive',
                config_rename_failed: 'Could not rename config file',
                config_read_failed: 'Could not read config file',
                config_write_failed: 'Could not write new config file',
                sqlinit_failed: 'Could not create default SQL tables',
                filedelete_failed: 'Could not delete file',
                move_failed: 'Could not move template contents to root web folder'
            }

            $('#infoform').submit(e => {
                e.preventDefault()
                if(!installable){
                    return
                }
                installable = false
                $('#installbutton').text('Installing...')
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