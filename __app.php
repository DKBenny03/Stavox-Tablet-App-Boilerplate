<?php

if(!isset($_SESSION)) {
    session_start();
}

	/**
	* Main class
	*/

	class App
	{
		private static $host = '127.0.0.1'; // Mysql host
		private static $user = ''; // Mysql user.
        private static $pass = ''; // Mysql password
        private static $defaultconn = ''; // Default mysql database
		private $dbConn = array();
        private static $port = '3306'; // Mysql port
        private static $ApiKey = ''; // Din apps api nøgle

        public static $echodebug = true; // Echo debug information true/false. I din app bør du nok slå dette fra, medmindre du tester

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

			return $DB;
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

            $stmt = $this->getConnection()->prepare('INSERT INTO serveruserdata (SteamID, Name, Rank, VIP, GangID, GangName) VALUES (:SteamID, :Name, :Rank, :VIP, :GangID, :GangName) ON DUPLICATE KEY UPDATE Name = :Name, Rank = :Rank, VIP = :VIP, GangID = :GangID, GangName = :GangName');

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
            echo '';
        }

        function echoNav(){ // Tilføjer din navbar til din side, så du ikke behøver at lave den i alle dine html dokumenter
            echo '';
        }

        function echoFooter(){ // Tilføjer din footer til din side... bla bla bla
            echo '';
        }

    }
    ?>