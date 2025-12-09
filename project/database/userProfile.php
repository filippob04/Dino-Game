<?php
    session_start(); // Gestisco la sessione

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { // se non sono loggato torno al loginForm
        header("Location: loginForm.php");
        exit();
    }

    // Configurazione Database
    $db_username = "player";
    $db_password = "userPassword";
    $serverName = "localhost";
    $dbName = "saw_project";

    // Inizializziamo le variabili
    $firstName = $lastName = $email = $bio = $message = "";
    
    // Messaggio di stato per l'aggiornamento bio
    $updateMessage = "";

    // Gestione Avatar
    $baseAvatarPath = "img/avatars/";
    define('API_AVATAR_URL', 'https://ui-avatars.com/api/'); // sonarqube
    $randomImgPath = API_AVATAR_URL . "?name=User&background=random";

    try {
        // mi connetto al db
        $conn = new mysqli($serverName, $db_username, $db_password, $dbName);
        if ($conn->connect_error) {
            throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
        }
        $conn->set_charset("utf8mb4");

        // se devo aggiornare la bio
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_bio'])) {
            $newBio = $_POST['bio'] ?? '';
            if (strlen($newBio) > 150) {
                $newBio = substr($newBio, 0, 150);
            }

            $sqlBio = "UPDATE stats SET bio = ? WHERE user_id = ?";
            $queryBio = $conn->prepare($sqlBio);
            $queryBio->bind_param("si", $newBio, $_SESSION['id']); // aggiorno la bio dell'utente in base all'id univoco chiave primaria
            if ($queryBio->execute()) {
                $updateMessage = "Biografia aggiornata!";
                $bio = $newBio;
            } else {
                $updateMessage = "Errore aggiornamento.";
            }
            $queryBio->close();
        }

        // ottengo i dati del profilo
        $sql = "SELECT
            u.firstName,
            u.lastName,
            u.email,
            s.hs,
            s.pt,
            s.bio,
            s.gamesPlayed
        FROM user u
        JOIN stats s ON u.id = s.user_id
        WHERE u.id = ?";
        $query = $conn->prepare($sql);
        $query->bind_param("i", $_SESSION['id']);
        if(!$query->execute()){
            throw new mysqli_sql_exception($query->error, $query->errno);
        }
        $result = $query->get_result();
        if ($row = $result->fetch_assoc()) { // fetch_assoc estrae tupla per tupla i dati della query
            $firstName = $row['firstName'];
            $lastName = $row['lastName'];
            $email = $row['email'];
            $pt = $row['pt'];
            $hs = $row['hs'];
            $bio = $row['bio'] ?? '';
            $gamesPlayed = $row['gamesPlayed'];
        } else {
            // se non trovo i dati anche se inizialmente erano corretti (utente bannato nel mentre)
            session_destroy();
            header("Location: loginForm.php");
            exit();
        }
        $query->close();
        $conn->close();

        // Gestione dell'immagine del profilo randomica
        $avatars = glob($baseAvatarPath . '*.png');
        if ($avatars && !empty($avatars)) {
            $randomImgPath = $avatars[array_rand($avatars)];
        }
    } catch (mysqli_sql_exception $e) {
        $message = "Errore Database: " . $e->getCode();
    } catch (Exception $e) {
        $message = "Errore generico" . $e->getCode();
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Profilo Utente - <?php echo htmlspecialchars($_SESSION['username']); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="profileStyle.css">
</head>
<body>
    <div class="login-container profile-container">
        
        <h2>PROFILO GIOCATORE</h2>
        
        <div class="avatar-box">
            <img src="<?php echo htmlspecialchars($randomImgPath); ?>" alt="Avatar Casuale" class="pixel-avatar">
        </div>

        <div class="highscore-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <p class="label">PUNTEGGIO TOTALE</p>
                    <p class="score-value text-white"><?php echo htmlspecialchars($pt); ?></p>
                </div>
                <div class="vertical-divider"></div>
                <div class="stat-item">
                    <p class="label">PUNTEGGIO MASSIMO</p>
                    <p class="score-value text-gold"><?php echo htmlspecialchars($hs); ?></p>
                </div>
                <div class="vertical-divider"></div>
                <div class="stat-item">
                    <p class="label">PARTITE GIOCATE</p>
                    <p class="score-value text-white"><?php echo htmlspecialchars($gamesPlayed); ?></p>
                </div>
            </div>
        </div>

        <hr class="divider">

        <div class="user-details">
            <p><strong>UTENTE:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            <p><strong>NOME:</strong> <?php echo htmlspecialchars($firstName . " " . $lastName); ?></p>
            <p><strong>EMAIL:</strong> <?php echo htmlspecialchars($email); ?></p>
        </div>

        <hr class="divider">

        <div class="bio-section">
            <form method="POST" action="">
                <div class="bio-header">
                    <p class="label-left">BIOGRAFIA:</p>
                    
                    <button type="submit" name="save_bio" id="saveBioBtn" class="btn-save-hidden" title="Salva Modifiche">
                        &#10004;
                    </button>
                </div>

                <?php if (!empty($updateMessage)): ?>
                    <p id="msgSuccess" style="font-size: 0.6rem; margin-bottom: 5px;">
                        <?php echo $updateMessage; ?>
                    </p>
                    <script>
                        setTimeout(function() { // dopo 2000 millisecondi scompare la scritta
                            var msg = document.getElementById('msgSuccess');
                            if (msg) msg.style.display = 'none';
                        }, 2000);
                    </script>
                <?php endif; ?>

                <textarea
                    name="bio"
                    id="bioText"
                    class="bio-textarea"
                    placeholder="Lorem Ipsum..."
                    maxlength="150"
                    data-original="<?php echo htmlspecialchars($bio); ?>"
                    oninput="checkBioChanges()"
                ><?php echo htmlspecialchars($bio); ?></textarea>
            </form>
        </div>

        <script>
        function checkBioChanges() {
            var textarea = document.getElementById('bioText');
            var btn = document.getElementById('saveBioBtn');
            
            var originalText = textarea.getAttribute('data-original');
            var currentText = textarea.value;

            if (originalText !== currentText) {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
            }
        }
        </script>

        <div class="action-buttons">
            <a href="../home/homepage.html" class="btn-play btn-small">HOME</a>
            <a href="logout.php" class="btn-play btn-small btn-logout">LOGOUT</a>
        </div>
        <div class="action-buttons">
            <a href="editProfile.php" class="btn-play btn-small">MODIFICA PROFILO</a>
        </div>
    </div>
</body>
</html>
