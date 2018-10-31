<?php

// Start en session hvis der ikke er nogen session i gang

if(!isset($_SESSION)) { 
    session_start();
}

// Class med vores funktioner

class App
	{

        // Indstillinger

		private static $host = '127.0.0.1'; // Mysql host - 127.0.0.1 er altid en computers egen ip, og fungerer derfor også til dette, da vi gerne vil kontakte MySQL serveren der ligger på samme server
		private static $user = ''; // Mysql user.
        private static $pass = ''; // Mysql password
        private static $defaultconn = ''; // Default mysql database
		private $dbConn = array();
        private static $port = '3306'; // Mysql port
        private static $ApiKey = ''; // Din apps api nøgle

        public static $echodebug = false; // Echo debug information true/false. I din app bør du nok slå dette fra, medmindre du tester
        public static $createdatabases = true; // Variabel der bestemmer om vi skal oprette vores eksempeltabeller. Sæt den til false når du har lavet tabellerne.

        // Funktioner

		function cleanInput($str){ // Input cleaning for bedre sikkerhed 
			$str = trim($str);
			$str = stripslashes($str);
			$str = htmlspecialchars($str);
			return $str;
        }

		function getConnection($connName = NULL) {
            if(!$connName){$connName = self::$defaultconn;}
			if(isset($this->dbConn[$connName]) && !empty($this->dbConn[$connName])) {
				return $this->dbConn[$connName];
			}

			$DB = new PDO('mysql:host='.self::$host.';dbname='.$connName.';port='.self::$port, self::$user, self::$pass);
			$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$DB->exec('set names utf8');

            $this->dbConn[$connName] = $DB;
            
            if(self::$createdatabases){ // Simpel funktion der laver de krævede tabeller. Du kan bare slette dette hvis du ikke har brug for det
                $this->createExampleSqlTables();
            }

			return $DB;
        }

        function createExampleSqlTables(){ // Simpel funktion der laver de krævede tabeller. Du kan bare slette dette hvis du ikke har brug for det
            $this->getConnection()->query('CREATE TABLE IF NOT EXISTS `serveruserdata` (
                `SteamID` VARCHAR(255) NOT NULL,
                `Name` VARCHAR(255) NOT NULL,
                `Rank` VARCHAR(255) NOT NULL,
                `VIP` INT(11) NOT NULL,
                `GangID` INT(11) NULL DEFAULT NULL,
                `GangName` VARCHAR(255) NULL DEFAULT NULL,
                `LastUpdated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`SteamID`)
            )
            COLLATE="utf8_general_ci"
            ENGINE=InnoDB
            ;');

            $this->getConnection()->query('CREATE TABLE IF NOT EXISTS`moneylog` (
                `SteamID` VARCHAR(255) NOT NULL,
                `Token` VARCHAR(255) NOT NULL,
                `Amount` INT(11) NOT NULL,
                PRIMARY KEY (`SteamID`)
            )
            COLLATE="utf8_general_ci"
            ENGINE=InnoDB
            ;');
        }

        function getApiKey(){ // Returnerer din api key (Defineret i toppen af denne fil)
            return self::$ApiKey;
        }

        function getNewServerUserData($token){ // Henter ny userdata fra stavox serveren
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => 'https://stavox.dk/tablet/apps/appstore/api.php?key='.$this->getApiKey().'&function=getplayerdata&playertoken='.$token,
                CURLOPT_USERAGENT => 'Min seje tablet app'
            ));
            $resp = curl_exec($curl);

            curl_close($curl);

            $resp = json_decode($resp, true);

            if(!$resp['success']){
                return false;
            }

            if(isset($resp['Gang'])){
                $resp['GangID'] = $resp['Gang']['GangID'];
                $resp['GangName'] = $resp['Gang']['GangName'];
            }
            else{
                $resp['GangID'] = NULL;
                $resp['GangName'] = NULL;
            }

            $stmt = $this->getConnection()->prepare('INSERT INTO `serveruserdata` (`SteamID`, `Name`, `Rank`, `VIP`, `GangID`, `GangName`) VALUES (:SteamID, :Name, :Rank, :VIP, :GangID, :GangName) ON DUPLICATE KEY UPDATE `Name` = :Name, `Rank` = :Rank, `VIP` = :VIP, `GangID` = :GangID, `GangName` = :GangName');

            // For guds skyld: Brug prepared statements. Det er hvad jeg gør i denne fil, og det er hvad du ALTID bør gøre.

            $stmt->bindParam(':SteamID', $resp['SteamID']);
            $stmt->bindParam(':Name', $resp['Name']);
            $stmt->bindParam(':Rank', $resp['Rank']);
            $stmt->bindParam(':VIP', $resp['VIP']);
            $stmt->bindParam(':GangID', $resp['GangID']);
            $stmt->bindParam(':GangName', $resp['GangName']);

            $stmt->execute();

            return $resp;

        }

        function getSteamID(){ // Henter SteamID fra spillerens session eller logger spilleren ind, og sætter en session token
            if(isset($_SESSION['SteamID'])){
                return $_SESSION['SteamID'];
            }
            else{
                $this->doLogin();
                return $_SESSION['SteamID'];
            }
        }

        function getServerUserData($SteamID = NULL){ // Denne funktion henter userdata fra mysql tabellen, så du ikke behøver at requeste nyt data fra serveren hver gang
            if(!$SteamID){$SteamID = $this->getSteamID();}

            $stmt = $this->getConnection()->prepare('SELECT * FROM serveruserdata WHERE SteamID = :SteamID');
            $stmt->bindParam(':SteamID', $SteamID);
            $stmt->execute();
            $res = $stmt->fetch();

            if(!isset($res['SteamID'])){
                return false;
            }

            return $res;

        }

        function DoLogin(){ // Logger en bruger ind
            if(isset($_SESSION['SteamID'])){
                if(self::$echodebug){ // Echo'er logintypen, hvis at appen er sat i debugmode (toppen af denne fil)
                    echo 'Logintype: Session';
                }
                return;
            }

            if(!isset($_GET['token'])){
                echo 'No token';
                exit;
            }

            $Token = $this->cleanInput($_GET['token']);

            $UserData = $this->getNewServerUserData($Token);

            if(!$UserData){
                echo 'Invalid token';
                exit;
            }

            $_SESSION['SteamID'] = $UserData['SteamID'];

            if(self::$echodebug){ // Echo'er logintypen, hvis at appen er sat i debugmode (toppen af denne fil)
                echo 'Logintype: Token';
            }
            return;
        }

        function echoHead(){ // Tilføjer din <head> block til din side, så du ikke behøver at lave den i alle dine html dokumenter
            echo '
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
                <meta name="description" content="">
                <meta name="author" content="">
                <link rel="icon" href="favicon.ico">
            
                <title>Bootstrap App Template</title>
            
                <!-- Bootstrap core CSS -->
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
            
                <!-- Vores CSS -->
                <link href="/css/style.css" rel="stylesheet">
            </head>
            <body>
            ';
        }

        function echoNav(){ // Tilføjer din navbar til din side, så du ikke behøver at lave den i alle dine html dokumenter. I dette eksempel bruger vi Bootstrap, så vi ikke rigtigt behøver at bruge tid på at lave et flot design.
            echo '
            <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
            <a class="navbar-brand" href="/">Min seje app</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon"></span>
            </button>
      
            <div class="collapse navbar-collapse" id="navbarsExampleDefault">
              <ul class="navbar-nav mr-auto">
                <li class="nav-item active">
                  <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="#">Link</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link disabled" href="#">Disabled</a>
                </li>
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Dropdown</a>
                  <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <a class="dropdown-item" href="#">Action</a>
                    <a class="dropdown-item" href="#">Another action</a>
                    <a class="dropdown-item" href="#">Something else here</a>
                  </div>
                </li>
              </ul>
            </div>
          </nav>';
        }

        function echoFooter(){ // Tilføjer din footer til din side... bla bla bla
            echo '
            <!-- Jquery og Bootstrap core javascript -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/js/bootstrap.min.js"></script>
            <!-- Vores flotte javascript -->
            <script src="/js/script.js"></script>
            </body>
            </html>';
        }

    }
    ?>