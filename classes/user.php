<?php
// This class contains all of our user-handling, like logging in, getting userdata and so on.
class User
{
    use Singleton;

    // Log in the user with either their session or their logintoken. You can change this as much as you want.
    public function login()
    {
        if (isset($_SESSION['APP_STEAMID']) && !empty($_SESSION['APP_STEAMID'])) { // If the user is logged in using the session
            return true;
        } else { // If the user is not logged in yet
            // If the token is somehow not set, we'll display this error
            if (!isset($_GET['token']) || empty($_GET['token'])) {
                Layout::i()->error('Login error', 'No login token was provided by client');
                exit;
            }

            // Get the playerdata from the token
            $Login = SxAPI::i()->getPlayerData($_GET['token']);

            // If we couldn't login the user, display a nice error message
            if (!$Login['success']) {
                Layout::i()->error('Login error', 'Login failed with code ' . $Login['error']);
                exit;
            }

            // Prepare a new SQL query to see if the user exists
            $stmt = SQL::i()->conn()->prepare('SELECT * FROM users WHERE steamid = :steamid');

            // Bind the parameters to the query, so that we can use the parameters without worrying about SQL injections.
            $stmt->bindParam(':steamid', $Login['SteamID']);

            // Execute the SQL query
            $stmt->execute();
            $DBUser = $stmt->fetch();

            if (isset($DBUser, $DBUser['steamid'])) { // Existing users
                // Prepare new SQL query to update the users data
                $stmt = SQL::i()->conn()->prepare('UPDATE users SET name = :name, vip = :vip, rank = :rank, lastseen = NOW() WHERE steamid = :steamid');

                // Bind the parameters to the query, so that we can use the parameters without worrying about SQL injections.
                $stmt->bindParam(':steamid', $Login['SteamID']);
                $stmt->bindParam(':name', $Login['Name']);
                $stmt->bindParam(':vip', $Login['VIP']);
                $stmt->bindParam(':rank', $Login['Rank']);
                
                // Execute the SQL query
                $stmt->execute();
            } else { // New users
                // Prepare new SQL query to create the user in our local database
                $stmt = SQL::i()->conn()->prepare('INSERT INTO users (steamid, name, vip, rank) VALUES(:steamid, :name, :vip, :rank)');

                // Same as above
                $stmt->bindParam(':steamid', $Login['SteamID']);
                $stmt->bindParam(':name', $Login['Name']);
                $stmt->bindParam(':vip', $Login['VIP']);
                $stmt->bindParam(':rank', $Login['Rank']);
                
                $stmt->execute();
            }

            // Caching of frequently-used variables
            $_SESSION['APP_CACHE_NAME'] = $Login['Name'];
            $_SESSION['APP_CACHE_RANK'] = $Login['Rank'];
            $_SESSION['APP_CACHE_VIP'] = $Login['VIP'];

            // Insert steamid into the session, actually completing the login
            $_SESSION['APP_STEAMID'] = $Login['SteamID'];
            $_SESSION['APP_LOGINTIME'] = time();

            return true;
        }
    }

    // Get data belonging to a specific user
    public function getUserData($SteamID = null)
    {
        // If no SteamID is passed to the function, we'll just use the SteamID of the currently signed-in user
        if (!isset($SteamID)) {
            $SteamID = $this->getSteamID();
        }

        // Prepare the SQL command
        $stmt = SQL::i()->conn()->prepare('SELECT * FROM users WHERE steamid = :steamid');
        // Bind the SteamID parameter, so we can use it safely in our query
        $stmt->bindParam(':steamid', $SteamID);
        // Execute SQL
        $stmt->execute();
        return $stmt->fetch();
    }

    // Get the SteamID of the currently logged in user
    public function getSteamID()
    {
        return $_SESSION['APP_STEAMID'];
    }

    // Get the RP name of the currently logged in user
    public function getName()
    {
        return $_SESSION['APP_CACHE_NAME'];
    }

    // Get the in-game rank of the currently logged in user
    public function getRank()
    {
        return $_SESSION['APP_CACHE_RANK'];
    }

    // Get the VIP status of the currently logged in user
    public function getVIP()
    {
        return $_SESSION['APP_CACHE_VIP'];
    }
}
