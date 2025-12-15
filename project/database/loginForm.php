<?php
    session_start(); // Gestisco la sessione

    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) { // se sono gia' loggato vado direttamente al profilo
        header("Location: userProfile.php");
        exit();
    }

    $message = "";
    $messageType = "";

    $username = "";
    $password_plain = "";

    $pendingScore = 0;
    if (isset($_SESSION['pending_score'])) { // punteggio in variabile di sessione
        $pendingScore = intval($_SESSION['pending_score']);
    }

    if($_SERVER["REQUEST_METHOD"] == "POST"){ // se ho ricevuto una richiesta di post (invio del form)
        // credenziali DB
        $db_username = "player";
        $db_password = "userPassword";
        $serverName = "localhost";
        $dbName = "saw_project";

        // Credenziali del form
        $username = ($_POST['username']) ?? '';
        $password_plain = $_POST['password'] ?? '';

        // check
        if (empty($username) || empty($password_plain)) {
            $message = "Attenzione: Tutti i campi devono essere compilati!";
            $messageType = "error";
        }

        // se username e password sono stati inseriti, message sara' vuoto
        if(empty($message)) {
            try {
                // tento di connetermi al server
                $conn = new mysqli($serverName, $db_username, $db_password, $dbName);
                if($conn->connect_error){
                    throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
                }
                $conn->set_charset("utf8mb4"); // Per gestire username con caratteri strani

                // prepared statement
                $sql = "SELECT id, username, securePassword FROM user WHERE username = ?";
                $query = $conn->prepare($sql);
                if(!$query){
                    throw new mysqli_sql_exception("SQL Prepare Error: " . $conn->error);
                }
                $query->bind_param("s", $username);
                // eseguo la query
                if(!$query->execute()){
                    throw new mysqli_sql_exception($query->error, $query->errno);
                }
                $result = $query->get_result();
                // verifico che result contenga la tupla (query ok)
                if ($row = $result->fetch_assoc()) {
                    if (password_verify($password_plain, $row['securePassword'])) { // se la password e' corretta
                    
                        session_regenerate_id(true); // riavvio la sessione: ora sono loggato, imposto i valori di sessione

                        $_SESSION['id'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['logged_in'] = true;

                        if ($pendingScore > 0) { // Aggiorniamo la riga appena creata con i punti della partita
                            $sqlScore = "UPDATE stats SET pt = pt + ?, hs = GREATEST(hs, ?), gamesPlayed = gamesPlayed + 1 WHERE user_id = ?";
                            $queryScore = $conn->prepare($sqlScore);
                            $queryScore->bind_param("iii", $pendingScore, $pendingScore, $_SESSION['id']);
                            if (!$queryScore->execute()) {
                                throw new mysqli_sql_exception($queryScore->error, $queryScore->errno);
                            }
                            $queryScore->close();
                        }

                        $query->close();
                        $conn->close();

                        unset($_SESSION['pending_score']);
                        header("Location: userProfile.php"); // vado al profilo
                        exit();

                    } else { // Password Errata
                        $message = "Username o Password errati";
                        $messageType = "error";
                    }
                } else { // Username Errato
                    $message = "Username o Password errati";
                    $messageType = "error";
                }
            
            $query->close();
            $conn->close();

            } catch (mysqli_sql_exception $e) {
                $message = "Errore Database: " . $e->getMessage();
                $messageType = "error";
            } catch (Exception $e) {
                $message = "Errore generico: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="it">
  <head>
    <meta charset="UTF-8" />
    <title>Form Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="formStyle.css" />
  </head>
  <body>

    <?php if (!empty($message)): ?>
            <p class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

    <div class="login-container">
      <h2>Form</h2>
        <form method="POST" action="">
            <h2>Login Utente</h2>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($username); ?>"><br><br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required ><br><br>

            <input type="submit" name="login" value="Login">
        </form>

        <a href="../database/registrationForm.php" class="btn-play">NON SEI REGISTRATO?</a>
    </div>
    <a href="../home/homepage.html" class="btn-play" style="border-color: #d32f2f; color: #d32f2f; margin-top: 30px;">
            TORNA ALLA HOME
        </a>
</body>
</html>
