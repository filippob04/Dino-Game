<?php
    session_start(); // Gestisco la sessione
    
    $message = "";
    $messageType = "";

    $username = "";
    $firstName = "";
    $lastName = "";
    $email = "";

    $pendingScore = 0;
    if (isset($_POST['pending_score'])) { // invio anche score tramite un campo nascosto del form in caso di ricarica della pagina
        $pendingScore = intval($_POST['pending_score']);
    } elseif (isset($_GET['score'])) { // score salvato nell'header
        $pendingScore = intval($_GET['score']);
    }

    if($_SERVER["REQUEST_METHOD"] == "POST"){ // Se ho inviato una richiesta di post (invio del form)
        // Credenziali DB
        $db_username = "player";
        $db_password = "userPassword";
        $serverName = "localhost";
        $dbName = "saw_project";

        // Ottengo i valori del form
        $username = ($_POST['username']) ?? '';
        $firstName = ($_POST['firstName']) ?? '';
        $lastName = ($_POST['lastName']) ?? '';
        $email = ($_POST['email']) ?? '';
        $password_plain = $_POST['password'] ?? '';
        $password_confirm = $_POST['confirm_password'] ?? '';

        // Check
        if (empty($username) || empty($email) || empty($password_plain) || empty($password_confirm) || empty($firstName) || empty($lastName)) {
            $message = "Attenzione: Tutti i campi devono essere compilati!";
            $messageType = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Attenzione: L'indirizzo Email fornito non è valido.";
            $email = "";
            $messageType = "error";
        } elseif ($password_plain !== $password_confirm) {
            $message = "Attenzione: Le due password inserite non corrispondono.";
            $password_confirm = $password_plain = "";
            $messageType = "error";
        } elseif (strlen($password_plain) < 8) {
            $message = "Attenzione: La Password deve aver un minimo di 8 caratteri";
            $password_confirm = $password_plain = "";
            $messageType = "error";
        }

        // Se non ho ricevuto errori (message e' vuoto)
        if(empty($message)) {
            $securePassword = password_hash($password_plain, PASSWORD_DEFAULT); // hash della password
            try {

                // connessione al db
                $conn = new mysqli($serverName, $db_username, $db_password, $dbName);
                if($conn->connect_error){
                    throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
                }
                $conn->set_charset("utf8mb4"); // Per gestire username con caratteri strani
                $conn->begin_transaction(); // Inizio la transazione

                // prepared statement
                $sql = "INSERT INTO user (username, firstName, lastName, email, securePassword) VALUES (?, ?, ?, ?, ?)";
                $query = $conn->prepare($sql);
                if(!$query){
                    throw new mysqli_sql_exception("SQL Prepare Error: " . $conn->error);
                }
                $query->bind_param("sssss", $username, $firstName, $lastName, $email, $securePassword);
                // eseguo la query
                if (!$query->execute()) {
                    throw new mysqli_sql_exception($query->error, $query->errno);
                }
                $newUserId = $conn->insert_id;
                $query->close();

                // query per tabella stats
                $sqlStats = "INSERT INTO stats (user_id, bio) VALUES (?, '')";
                $queryStats = $conn->prepare($sqlStats);
                $queryStats->bind_param("i", $newUserId);
                if (!$queryStats->execute()) {
                    throw new mysqli_sql_exception($queryStats->error, $queryStats->errno);
                }
                $queryStats->close();

                if ($pendingScore > 0) { // Aggiorniamo la riga appena creata con i punti della partita
                    $sqlScore = "UPDATE stats SET pt = ?, hs = ?, gamesPlayed = gamesPlayed + 1 WHERE user_id = ?";
                    $queryScore = $conn->prepare($sqlScore);
                    $queryScore->bind_param("iii", $pendingScore, $pendingScore, $newUserId);

                    if (!$queryScore->execute()) {
                        throw new mysqli_sql_exception($queryScore->error, $queryScore->errno);
                    }
                    $queryScore->close();
                }

                $conn->commit(); // Commit

                session_regenerate_id(true); // riavvio la sessione: ora sono loggato, imposto i valori di sessione

                $_SESSION['id'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['logged_in'] = true;

                header("Location: userProfile.php"); // vado al profilo
                exit();
            } catch (mysqli_sql_exception $e) {
                $conn->rollback(); // rollback della transazione

                if ($e->getCode() === 1062) { // 1062 e' il codice per dati duplicati
                    $message = "Attenzione: Username o Email già esistenti.";
                    $messageType = "error";
                    $username = $email = "";
                } else {
                    $message = "Errore Database: " . $e->getMessage();
                    $messageType = "error";
                }
                $messageType = "error";
            } catch (Exception $e) {
                $conn->rollback();

                $message = "Errore generico: " . $e->getMessage();
                $messageType = "error";
            } finally { // chiudo la connessione
                if (isset($conn)) {
                    $conn->close();
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="it">
  <head>
    <meta charset="UTF-8" />
    <title>Form Registrazione</title>
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
            <h2>Registrazione Nuovo Utente</h2>
            <input type="hidden" name="pending_score" value="<?php echo $pendingScore; ?>"> <!--Score Nascosto-->

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($username); ?>"><br><br>

            <label for="firstName">Nome:</label>
            <input type="text" id="firstName" name="firstName" required value="<?php echo htmlspecialchars($firstName); ?>"><br><br>
            

            <label for="lastName">Cognome:</label>
            <input type="text" id="lastName" name="lastName" required value="<?php echo htmlspecialchars($lastName); ?>"><br><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>"><br><br>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required ><br><br>

            <label for="confirm_password">Conferma Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required><br><br>

            <input type="submit" name="register" value="Registrati">
        </form>
        <a href="../database/loginForm.php?score=<?php echo $pendingScore; ?>" class="btn-play">EFFETTUA IL LOGIN</a>
    </div>
    <a href="../home/homepage.html" class="btn-play" style="border-color: #d32f2f; color: #d32f2f; margin-top: 30px;">
            TORNA ALLA HOME
        </a>
</body>
</html>
