<?php
    session_start(); // inizio sessione
    require_once '../../util/config.php'; // Carica le costanti

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { // Se non sono loggato torno al form di login
        header("Location: loginForm.php");
        exit();
    }

    $message = "";
    $messageType = "";
    
    $username = "";
    $firstName = "";
    $lastName = "";
    $email = "";

    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if($conn->connect_error){
            throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
        }
        $conn->set_charset("utf8mb4"); // per gestire caratteri strani

        // Invio del form di update
        if($_SERVER["REQUEST_METHOD"] == "POST"){
            
            $newUsername = $_POST['username'] ?? '';
            $newFirstName = $_POST['firstName'] ?? '';
            $newLastName = $_POST['lastName'] ?? '';
            $newEmail = $_POST['email'] ?? '';
            
            // Se decido di aggiornare anche la password
            $newPasswordPlain = $_POST['password'] ?? '';
            $newPasswordConfirm = $_POST['confirm_password'] ?? '';
            
            // Password corrente
            $currentPassword = $_POST['verify_password'] ?? '';

            // Validazione Base
            if (empty($newUsername) || empty($newEmail) || empty($newFirstName) || empty($newLastName)) {
                $message = "Attenzione: Nome, Cognome, Username ed Email sono obbligatori.";
                $messageType = "error";
            } elseif (empty($currentPassword)) {
                $message = "Per salvare le modifiche devi inserire la tua password attuale.";
                $messageType = "error";
            } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $message = "Email non valida.";
                $messageType = "error";
            } elseif ($newPasswordPlain !== $newPasswordConfirm) {
                $message = "Le nuove password non corrispondono.";
                $messageType = "error";
            } elseif (!empty($newPasswordPlain) && strlen($newPasswordPlain) < 8) {
                $message = "La nuova password deve avere almeno 8 caratteri.";
                $messageType = "error";
            }

            // se non ho commesso errori
            if(empty($message)) {
                // Verifico la correttezza della password per il salvataggio
                $sqlCheckPwd = "SELECT securePassword FROM user WHERE id = ?";
                $queryCheckPwd = $conn->prepare($sqlCheckPwd);
                $queryCheckPwd->bind_param("i", $_SESSION['id']);
                if(!$queryCheckPwd->execute()){
                    throw new mysqli_sql_exception($queryCheckPwd->error, $queryCheckPwd->errno);
                }
                $checkPwd = $queryCheckPwd->get_result();
                if(!$rowCheckPwd = $checkPwd->fetch_assoc()){
                    // Utente non trovato (rimosso dal db nel mentre)
                    session_destroy();
                    header("Location: loginForm.php");
                    exit();
                }
                $queryCheckPwd->close();

                // verifico la password inserita con quella attuale
                if (!$rowCheckPwd || !password_verify($currentPassword, $rowCheckPwd['securePassword'])) {
                    $message = "La password attuale inserita non è corretta. Modifiche annullate.";
                    $messageType = "error";
                } else {
                    $passwordToStore = $rowCheckPwd['securePassword']; // Di base tengo la vecchia
                    if (!empty($newPasswordPlain)) {
                        $passwordToStore = password_hash($newPasswordPlain, PASSWORD_DEFAULT);
                    }

                    try {
                        $conn->begin_transaction(); // inizio la transazione per far si che in caso di errore le modifiche vengano ripristinate

                        $sqlUpdate = "UPDATE user SET username=?, firstName=?, lastName=?, email=?, securePassword=? WHERE id=?";
                        $queryUpdate = $conn->prepare($sqlUpdate);
                        $queryUpdate->bind_param("sssssi", $newUsername, $newFirstName, $newLastName, $newEmail, $passwordToStore, $_SESSION['id']);
                        if($queryUpdate->execute()) {
                            $conn->commit();
                            $message = "Profilo aggiornato con successo!";
                            $messageType = "success";

                            $_SESSION['username'] = $newUsername; // aggiorno la variabile di sessione
                        } else {
                            throw new mysqli_sql_exception($queryUpdate->error, $queryUpdate->errno);
                        }
                        $queryUpdate->close();

                    } catch (mysqli_sql_exception $e) {
                        $conn->rollback(); // rollback
                        if ($e->getCode() === 1062) {
                            $message = "Username o Email già in uso da un altro utente.";
                        } else {
                            $message = "Errore SQL: " . $e->getMessage();
                        }
                        $messageType = "error";
                    }
                }
            }
        }
        // Dati presenti sul database (per mostrarli all'utente)
        $sqlSelect = "SELECT username, firstName, lastName, email FROM user WHERE id = ?";
        $querySelect = $conn->prepare($sqlSelect);
        $querySelect->bind_param("i", $_SESSION['id']);
        if(!$querySelect->execute()){
            throw new mysqli_sql_exception($querySelect->error, $querySelect->errno);
        }
        $result = $querySelect->get_result();
        if ($row = $result->fetch_assoc()) {
            $username = $row['username'];
            $firstName = $row['firstName'];
            $lastName = $row['lastName'];
            $email = $row['email'];
        } else {
            // Utente non trovato (rimosso dal db nel mentre)
            session_destroy();
            header("Location: loginForm.php");
            exit();
        }
        $querySelect->close();
        $conn->close();

    } catch (mysqli_sql_exception $e) {
        $message = "Errore Database: " . $e->getMessage();
        $messageType = "error";
    } catch (Exception $e) {
        $message = "Errore di sistema: " . $e->getMessage();
        $messageType = "error";
    }
?>

<!DOCTYPE html>
<html lang="it">
  <head>
    <meta charset="UTF-8" />
    <title>Modifica Profilo</title>
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/formStyle.css" />
  </head>
  <body>

    <?php if (!empty($message)): ?>
        <div class="login-container" style="margin-bottom: 20px; padding: 10px; border-color: <?php echo ($messageType == 'error') ? '#d32f2f' : '#388e3c'; ?>;">
            <p class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="login-container">
      <h2>Modifica Profilo</h2>
        <form method="POST" action="">
            
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($username); ?>"><br>

            <label for="firstName">Nome:</label>
            <input type="text" id="firstName" name="firstName" required value="<?php echo htmlspecialchars($firstName); ?>"><br>
            
            <label for="lastName">Cognome:</label>
            <input type="text" id="lastName" name="lastName" required value="<?php echo htmlspecialchars($lastName); ?>"><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>"><br>

            <div class="divider"></div>
            
            <p style="font-size: 0.6rem; color: #535353; margin-bottom: 10px;">Compila solo se vuoi cambiare password:</p>
            
            <label for="password">Nuova Password:</label>
            <input type="password" id="password" name="password" placeholder="Lascia vuoto per non cambiare"><br>

            <label for="confirm_password">Conferma Nuova Password:</label>
            <input type="password" id="confirm_password" name="confirm_password"><br>

            <div class="divider"></div>

            <label for="verify_password" style="color: #d32f2f;">PASSWORD ATTUALE (Richiesto):</label>
            <input type="password" id="verify_password" name="verify_password" required placeholder="Inserisci la tua password attuale per confermare"><br><br>

            <input type="submit" name="commit" value="SALVA MODIFICHE">
        </form>
        <a href="deleteAccount.php" class="btn-play" style="border-color: #d32f2f; color: #d32f2f; margin-top: 30px;" >ELIMINARE ACCOUNT</a>
        <a href="userProfile.php" class="btn-play">TORNA AL PROFILO</a>
    </div>
</body>
</html>
