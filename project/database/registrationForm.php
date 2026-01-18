<?php
    session_start(); // Gestisco la sessione
    require_once '../../util/config.php'; // Carica le costanti
    
    $message = "";
    $username = "";
    $firstName = "";
    $lastName = "";
    $email = "";

    $pendingScore = 0;
    if (isset($_SESSION['pending_score'])) { // punteggio in variabile di sessione
        $pendingScore = intval($_SESSION['pending_score']);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST"){ // Se ho inviato una richiesta di post (invio del form)
        // Ottengo i valori del form
        $username = trim($_POST['username'] ?? '');
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password_plain = $_POST['password'] ?? '';
        $password_confirm = $_POST['confirm_password'] ?? '';

        // Check
        if (empty($username) || empty($email) || empty($password_plain) || empty($password_confirm) || empty($firstName) || empty($lastName)) {
            $message = "Attenzione: Tutti i campi devono essere compilati!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Attenzione: L'indirizzo Email fornito non è valido.";
            $email = "";
        } elseif ($password_plain !== $password_confirm) {
            $message = "Attenzione: Le due password inserite non corrispondono.";
            $password_confirm = $password_plain = "";
        } elseif (strlen($password_plain) < 8) {
            $message = "Attenzione: La Password deve aver un minimo di 8 caratteri";
            $password_confirm = $password_plain = "";
        }

        // Se non ho ricevuto errori (message e' vuoto)
        if (empty($message)) {
            try {
                $securePassword = password_hash($password_plain, PASSWORD_DEFAULT); // hash della password
                // connessione al db
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if ($conn->connect_error){
                    throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
                }
                $conn->set_charset("utf8mb4"); // Per gestire username con caratteri strani
                $conn->begin_transaction(); // Inizio la transazione

                // prepared statement
                $sql = "INSERT INTO user (username, firstName, lastName, email, securePassword) VALUES (?, ?, ?, ?, ?)";
                $query = $conn->prepare($sql);
                if (!$query){
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
                if ($pendingScore >= 0) {
                    $sqlStats = "INSERT INTO stats (user_id, pt, hs, gamesPlayed, bio) VALUES (?, ?, ?, 1, '')";
                    $queryStats = $conn->prepare($sqlStats);
                    $queryStats->bind_param("iii", $newUserId, $pendingScore, $pendingScore);
                } else {
                    // Utente normale senza punteggio in sospeso
                    $sqlStats = "INSERT INTO stats (user_id, pt, hs, gamesPlayed, bio) VALUES (?, 0, 0, 0, '')";
                    $queryStats = $conn->prepare($sqlStats);
                    $queryStats->bind_param("i", $newUserId);
                }

                if (!$queryStats->execute()) {
                    throw new mysqli_sql_exception($queryStats->error, $queryStats->errno);
                }
                $queryStats->close();

                $conn->commit(); // Commit

                session_regenerate_id(true); // riavvio la sessione: ora sono loggato, imposto i valori di sessione

                $_SESSION['id'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['logged_in'] = true;

                $conn->close();
                unset($_SESSION['pending_score']);

                header("Location: userProfile.php"); // vado al profilo
                exit();
            } catch (mysqli_sql_exception $e) {
                $conn->rollback(); // rollback della transazione
                if ($e->getCode() === 1062) { // 1062 e' il codice per dati duplicati
                    $message = "Attenzione: Username o Email già esistenti.";
                    $username = $email = "";
                } else {
                    $message = "Errore Database: " . $e->getMessage();
                }
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Errore generico: " . $e->getMessage();
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="it">
  <head>
    <meta charset="UTF-8" />
    <title>Form Registrazione</title>
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/formStyle.css" />
  </head>
  <body>
    <?php if (!empty($message)): ?>
        <p class="message error">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>

    <div class="login-container">
      <h2>Form</h2>
        <form method="POST" action="">
            <h2>Registrazione Nuovo Utente</h2>
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
        <a href="../database/loginForm.php" class="btn-play">EFFETTUA IL LOGIN</a>
    </div>
    <a href="../home/homepage.html" class="btn-play" style="border-color: #d32f2f; color: #d32f2f; margin-top: 30px;">TORNA ALLA HOME</a>
</body>
</html>
