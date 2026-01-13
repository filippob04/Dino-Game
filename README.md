# Dino Game - SAW Project A.A. 25-26

![Project Banner](util/preview.png)

> **Progetto universitario per il corso di Sviluppo Applicazioni Web (SAW)** > **Università degli Studi di Genova (UniGe) - Laurea Triennale in Informatica**

Un'applicazione web completa che combina un **Browser Game in stile Pixel Art** (ispirato al classico di Chrome) con un sistema di backend per la gestione utenti, salvataggio punteggi e classifiche.

## ✨ Funzionalità Principali

### 🎮 Il Gioco (Frontend)

- **Endless Runner:** Meccaniche di salto e abbassamento (_crouching_) per evitare ostacoli.
- **Difficoltà Progressiva:** La velocità e la frequenza degli ostacoli aumentano con il punteggio.
- **Ostacoli Vari:**
  - 🌵 Cactus singoli e gruppi (dimensioni e hitbox variabili).
  - 🦅 Pterodattili (richiedono il _ducking_).
- **Eventi Audio:** .mp3 recuperati dal gioco originale quando si (_salta_), (_perde_), (_accumulano punti_).
- **Grafica Pixel Art:** Sprite animati per la corsa, salto e collisioni.
- **Backup Locale:** Salvataggio temporaneo in `localStorage` per non perdere i progressi in caso di disconnessione.

### 🔐 Backend & Sicurezza (PHP/MySQL)

- **Autenticazione Sicura:**
  - Login e Registrazione con **Password Hashing** (`password_hash` / `password_verify`).
  - Protezione da **SQL Injection** tramite Prepared Statements (`mysqli`).
  - Prevenzione **Session Fixation** (`session_regenerate_id`).
  - Protezione **XSS** (Sanitizzazione output`htmlspecialchars`).
- **Gestione Profilo:**
  - Modifica dati e Bio.
  - Conferma tramite password attuale per modifiche sensibili.
  - Avatar casuale selezionato dal sistema.
  - Eliminazione **irreversibile** dell'account ai sensi del GDPR
- **Sistema di Punteggio:**
  - Salvataggio persistente su Database.
  - Logica transazionale per garantire l'integrità dei dati (Tabella `user` + `stats`).
  - **Leaderboard:** Classifica dei migliori 10 giocatori.

## 🛠️ Implementazione

- **Frontend:** HTML5, CSS3 (Custom Pixel Art Style), JavaScript (Vanilla ES6).
- **Backend:** PHP
- **Database:** MySQL / MariaDB.
- **Server:** Apache (via XAMPP/MAMP).

## 📂 Struttura del Progetto

```text
PROJECT/
├── database/                   # Script PHP per la logica backend
│   ├── img/avatars/            # Asset grafici per i profili
│   ├── style/                  # .css Stili
│   ├── loginForm.php           # Gestione Login
│   ├── registrationForm.php    # Gestione Registrazione
│   ├── logout.php              # Gestione Logout
│   ├── userProfile.php         # Dashboard Utente
│   ├── editProfile.php         # Modifica Dati Utente
│   ├── deleteAccount.php       # Eliminazione Dati Utente
│   └── saveGame.php            # Logica salvataggio punteggi
├── game/                       # Il Gioco JS
│   ├── assets/                 # Cartella Immagini/Audio
│   │   ├── img/                # Sprite e elementi .png
│   │   └── audio/              # Audio .mp3
│   ├── style.css               # Stili specifici per il gioco
│   ├── script.js               # Logica di gioco e collisioni
│   └── page.html               # Pagina di gioco
├── home/                       # Pagine pubbliche
│   ├── style.css               # Stili homepage.html, rules.html
│   ├── homepage.html           # Landing page
│   ├── rules.html              # Elenco Regole
│   └── leaderboard.php         # Classifica globale
└── util/                       # Risorse extra
    └── config.php, query.sql   # Credenziali e creazione DB
```

**Avvia il Server:**
_ Avvia i moduli **Apache** e **MySQL** dal pannello di controllo di XAMPP/MAMP.
_ Apri il browser e vai all'indirizzo:
`http://localhost/[...]project/project/home/homepage.html`
_(Il percorso potrebbe variare in base al nome della tua cartella in htdocs)_.

## 🗄️ Schema Database

Il progetto utilizza un database relazionale (**MySQL**) strutturato per garantire l'integrità dei dati e la separazione delle responsabilità.

### Diagramma ER Semplificato

> **User** (1) ──── (1) **Stats**

### 1. Tabella `user`

Contiene le informazioni di autenticazione e anagrafica.

| Colonna          | Tipo         | Note                                |
| :--------------- | :----------- | :---------------------------------- |
| `id`             | INT          | **PK**, Auto Increment              |
| `username`       | VARCHAR(100) | Unique, usato per il login          |
| `email`          | VARCHAR(255) | Unique                              |
| `firstName`      | VARCHAR(100) | Nome dell'utente                    |
| `lastName`       | VARCHAR(100) | Cognome dell'utente                 |
| `securePassword` | VARCHAR(255) | Hash generato con `password_hash()` |

### 2. Tabella `stats`

Contiene i dati di gioco e il profilo pubblico. Collegata all'utente tramite Foreign Key con cancellazione a cascata (`ON DELETE CASCADE`).

| Colonna       | Tipo         | Note                                          |
| :------------ | :----------- | :-------------------------------------------- |
| `user_id`     | INT          | **PK, FK** (Rif. `user.id`)                   |
| `pt`          | INT          | Punti Totali (Accumulati in tutte le partite) |
| `hs`          | INT          | **High Score** (Miglior partita singola)      |
| `gamesPlayed` | INT          | Numero totale di partite giocate              |
| `bio`         | VARCHAR(150) | Biografia personalizzabile (Max 150 char)     |

## 🕹️ Comandi di Gioco

Il gioco è accessibile sia da desktop che da dispositivi con tastiera.

| Tasto       | Azione        | Descrizione                                  |
| :---------- | :------------ | :------------------------------------------- |
| **SPAZIO**  | **Salto**     | Premi per saltare i Cactus.                  |
| **CTRL SX** | **Abbassati** | Tieni premuto per schivare gli Pterodattili. |

> **Nota:** La velocità del gioco e la frequenza degli ostacoli aumentano dinamicamente in base al punteggio raggiunto.

## 👤 Autore

**filippob04**

- Studente di Informatica - Università degli Studi di Genova (UniGe)
- Corso: Sviluppo Applicazioni Web (SAW) A.A. 25-26
- GitHub: [@filippob04](https://github.com/filippob04)

---

_Progetto sviluppato a scopo didattico. Grafiche e Sprite ispirati al gioco "Dino Runner" di Google Chrome._
