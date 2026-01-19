<?php
    session_start(); // inizio sessione
    require_once '../../util/config.php'; // Carica le costanti

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { // Se non sono loggato torno al form di login
        header("Location: loginForm.php");
        exit();
    }

    $message = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST"){
        $userPassword = $_POST['verify_password'] ?? '';
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error){
                throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
            }

            $sql = "SELECT securePassword FROM user WHERE id = ?";
            $query = $conn->prepare($sql);
            $query->bind_param("i", $_SESSION['id']);
            if (!$query->execute()){
                throw new mysqli_sql_exception($query->error, $query->errno);
            }
            $res = $query->get_result();
            if (!$row = $res->fetch_assoc()){
                // Utente non trovato (rimosso dal db nel mentre)
                session_destroy();
                header("Location: loginForm.php");
                exit();
            }
            $query->close();

            // verifico la password inserita con quella attuale
            if (!password_verify($userPassword, $row['securePassword'])) {
                $message = "La password attuale inserita non è corretta.";
            } else {
                try {
                    $conn->begin_transaction(); // inizio la transazione per far si che in caso di errore le modifiche vengano ripristinate

                    $sqlDelete = "DELETE FROM user WHERE id=?";
                    $queryDelete = $conn->prepare($sqlDelete);
                    $queryDelete->bind_param("i", $_SESSION['id']);
                    if ($queryDelete->execute()) {
                        $conn->commit();
                        $message = "Account Eliminato";
                        $conn->close();
                        
                        $_SESSION = array(); // svuota i dati di sessione (array)
                        session_destroy();

                        header("Location: ../home/homepage.html"); // rederict al login
                        exit();
                    } else {
                        throw new mysqli_sql_exception($queryDelete->error, $queryDelete->errno);
                    }
                    $queryDelete->close();

                } catch (mysqli_sql_exception $e) {
                    $conn->rollback(); // rollback
                    $message = "Si e' verificato un errore, riprova piu' tardi.";
                    error_log("Errore Database: " . $e->getCode() . $e->getMessage());
                }
            }
            if($conn) {
                $conn->close();
            }
        } catch (Exception $e) {
            $message = "Si e' verificato un errore, riprova piu' tardi.";
            error_log("Errore Generico: " . $e->getCode() . $e->getMessage());
        }
    }
?>
<!DOCTYPE html>
<html lang="it">
  <head>
    <meta charset="UTF-8" />
    <title>Elimina Profilo</title>
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/formStyle.css" />
  </head>
  <body>
    <?php if (!empty($message)): ?>
        <p class="message error">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>

    <div class="login-container" style="border-color: #d32f2f;">
        <form method="POST" action="">
            <h2 style="color: #d32f2f;">Eliminazione Account</h2>
            
            <p style="text-align: center; font-weight: bold; margin: 10px 0;">
                Utente: <?php echo htmlspecialchars($_SESSION['username']); ?>
            </p>

            <div style="font-size: 0.6rem; line-height: 1.6; color: #535353; margin: 20px 0; text-align: justify; border: 1px dashed #ccc; padding: 10px;">
                <strong>INFORMATIVA GDPR (DIRITTO ALL'OBLIO)</strong><br><br>
                In conformità con l'Art. 17 del GDPR, procedendo con l'eliminazione eserciti il tuo diritto alla cancellazione dei dati.
                Tutti i tuoi dati personali (email, nome, cognome) e i dati di gioco (statistiche, punteggi) verranno
                <strong>rimossi definitivamente</strong> dai nostri server e non potranno essere recuperati.
            </div>

            <div class="divider"></div>

            <input type="password" id="verify_password" name="verify_password" style="color: #d32f2f;" required placeholder="Inserisci la tua password attuale per confermare">

            <div class="divider"></div>

            <input type="submit" name="delete" class="btn-play" style="border-color: #d32f2f; color: #d32f2f; margin-top: 30px;" value="CONFERMA ED ELIMINA">
        </form>
        
        <a href="userProfile.php" class="btn-play">ANNULLA</a>
    </div>
</body>
</html>
