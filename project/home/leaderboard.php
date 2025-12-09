<?php
    // Credenziali per SELECT (minimo privilegio)
    $host = "localhost";
    $user = "viewer";
    $pass = "viewerPassword";
    $db   = "saw_project";

    $data = []; // Array dati

    try { // Connessione al DB
        $conn = new mysqli($host, $user, $pass, $db);
        
        if ($conn->connect_error) {
            throw new mysqli_sql_exception($conn->connect_error, $conn->connect_errno);
        }

        // Query
        $sql = "SELECT u.username, s.hs
                FROM user u
                JOIN stats s ON u.id = s.user_id
                ORDER BY s.hs DESC
                LIMIT 10";
        $result = $conn->query($sql);

        if ($result) {$data = $result->fetch_all(MYSQLI_ASSOC);} // fetch_all estrae tutti i dati della query
        
        $conn->close();

    } catch (mysqli_sql_exception $e) {
            error_log("DB Error: " . $e->getMessage());
    } catch (Exception $e) {
            error_log("Generic Error:", $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>leaderboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    
    <link href="https://unpkg.com/tabulator-tables@5.6.1/dist/css/tabulator_simple.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f7f7f7;
            color: #535353;
            font-family: 'Press Start 2P', cursive;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        h1 {
            margin-bottom: 40px;
            text-transform: uppercase;
        }
        #leaderboard-table {
            width: 100%;
            max-width: 600px;
            border: 4px solid #535353;
            margin-bottom: 40px;
            font-size: 0.8rem;
        }
        .tabulator {
            font-family: 'Press Start 2P', cursive !important;
            border: none !important;
        }
        .tabulator-header {
            background-color: #535353 !important;
            color: #535353 !important;
            border-bottom: 4px solid #535353 !important;
        }

        .tabulator-col-title {
            padding: 10px !important;
        }
        .tabulator-row {
            background-color: #fff !important;
            color: #535353 !important;
            border-bottom: 2px solid #535353 !important;
        }
        .tabulator-row.tabulator-row-even {
            background-color: #eee !important;
        }
        .tabulator-row:hover {
            background-color: #acacac !important;
            color: #fff !important;
            cursor: default;
        }

        .tabulator-cell {
            padding: 15px !important;
            border-right: 2px solid #535353 !important;
        }
        .btn-back {
            text-decoration: none;
            color: #535353;
            border: 2px solid #535353;
            padding: 15px 30px;
            font-size: 1rem;
            transition: transform 0.1s;
        }
        .btn-back:hover {
            background-color: #535353;
            color: #fff;
        }
        footer {
            position: absolute;
            bottom: 20px;
            font-size: 0.6rem;
            color: #acacac;
        }

    </style>
</head>
<body>

    <header>
        <h1>GIOCATORI MIGLIORI</h1>
    </header>

    <div id="leaderboard-table"></div>

    <a href="homepage.html" class="btn-back">INDIETRO</a>

    <script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.6.1/dist/js/tabulator.min.js"></script>
    
    <!-- Gestione della Tabella -->
    <script>
        var tableData = <?php echo json_encode($data); ?>;
        var table = new Tabulator("#leaderboard-table", {
            data: tableData,
            layout: "fitColumns",
            responsiveLayout: "hide",
            placeholder: "NO DATA",
            columns: [
                {
                    title: "Giocatore",
                    field: "username",
                    headerSort: false,
                    formatter: "textarea"
                },
                {
                    title: "Punteggio",
                    field: "hs",
                    hozAlign: "right",
                    headerSort: false,
                    width: 150
                },
            ],
        });
    </script>
    <footer>RIUSCIRAI A ESSER BRAVO COME LORO?</footer>
</body>
</html>
