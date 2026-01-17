<?php
    session_start(); // Gestisco la sessione
    require_once '../../util/config.php'; // Carica le costanti

    if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
        unset($_SESSION['pending_score']); // Rimuovo il punteggio in sospeso
        header("Location: ../home/homepage.html"); // Reindirizzo alla home
        exit();
    }

    $currentScore = 0;
    $message = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['score'])) {
        $currentScore = intval($_POST['score']);
        $_SESSION['pending_score'] = $currentScore;
    } elseif (isset($_SESSION['pending_score'])) {
        $currentScore = $_SESSION['pending_score'];
    }  else {
        header("Location: ../home/homepage.html"); // Reindirizzo alla home
        exit();
    }

    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) { // se sono gia' loggato vado direttamente al profilo

        try {
            // mi connetto al db
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
            }
            $conn->set_charset("utf8mb4");

            $sql = "UPDATE stats SET pt = pt + ?, hs = GREATEST(hs, ?), gamesPlayed = gamesPlayed + 1 WHERE user_id = ?";
            $query = $conn->prepare($sql);
            $query->bind_param("iii",$currentScore, $currentScore, $_SESSION['id']);
            if(!$query->execute()){
                throw new mysqli_sql_exception($query->error, $query->errno);
            }
            $query->close();
            $conn->close();

            unset($_SESSION['pending_score']);
            header("Location: userProfile.php");
            exit();
        } catch (mysqli_sql_exception $e) {
            $message = "Errore Database: " . $e->getCode();
        } catch (Exception $e) {
            $message = "Errore generico" . $e->getCode();
        }
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Salva Partita</title>
    <link rel="stylesheet" href="style/style.css">
    <link rel="stylesheet" href="style/formStyle.css">
</head>
<body>
    <div class="login-container" style="text-align: center;">
        <h2>GAME OVER!</h2>

        <?php if (!empty($message)): ?>
            <div style="background-color: #ffebee; color: #c62828; padding: 10px; border: 1px solid #ef9a9a; margin-bottom: 15px; font-size: 0.8rem;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <p style="margin-bottom: 20px;">
            Hai totalizzato <strong><?php echo $currentScore; ?></strong> punti.
        </p>

        <p style="font-size: 0.7rem; margin-bottom: 20px;">Vuoi salvare il punteggio?</p>

        <a href="registrationForm.php" class="btn-play">
            REGISTRATI E SALVA
        </a>

        <a href="loginForm.php" class="btn-play" style="margin-top: 10px;">
            HAI GIÀ UN ACCOUNT? LOGIN
        </a>

        <a href="?action=cancel" class="btn-play" style="border-color: #d32f2f; color: #d32f2f; margin-top: 30px;">
            TORNA ALLA HOME
        </a>
    </div>
</body>
</html>
