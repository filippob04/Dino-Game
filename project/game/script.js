const dino = document.getElementById("dino");
const gameContainer = document.querySelector(".game-container");
const scoreElement = document.getElementById("score");
const startMessage = document.getElementById("start-message");
const gameOverMsg = document.getElementById("game-over-msg");
const finalScoreSpan = document.getElementById("final-score");
const muteBtn = document.getElementById("mute-btn");

/* variabili per punteggio e meccaniche gioco */
let score = 0;
let isGameStarted = false;
let isGameOver = false;
let dinoTop = 312; // Valore di default
let gameLoopId;

/* gestione animazioni */
let isRightFoot = true;
let runAnimationId;
let isDucking = false;

/* gestione asset audiovisivi */
let isMuted = true;
const jumpSound = new Audio("assets/audio/jump.mp3");
const deathSound = new Audio("assets/audio/die.mp3");
const pointSound = new Audio("assets/audio/point.mp3");

jumpSound.preload = "auto";
deathSound.preload = "auto";
pointSound.preload = "auto";
jumpSound.load();
deathSound.load();
pointSound.load();

function preloadImages() {
  const images = [
    "assets/img/dino_l.png",
    "assets/img/dino_r.png",
    "assets/img/duck_l.png",
    "assets/img/duck_r.png",
    "assets/img/dino_dead.png",
    "assets/img/audioOn.png",
    "assets/img/audioOff.png",
  ];
  for (const src of images) {
    const img = new Image();
    img.src = src;
  }
}
preloadImages();

/* Interazioni con la tastiera */
// eventlistener dello spazio per chiamare spawnObstacle
document.addEventListener("keydown", function (event) {
  if (isGameOver) return;
  if (event.code === "Space") {
    if (startMessage) startMessage.style.display = "none";

    if (!isGameStarted) {
      isGameStarted = true;
      startGameLoop();
      spawnObstacle();
    }
    jump();
  }
});

// eventlistener per il ctrl
document.addEventListener("keydown", function (event) {
  if (isGameOver) return;
  if (event.code === "ControlLeft") {
    if (!isDucking) {
      isDucking = true;
      dino.classList.add("dino-ducking");
      animateRunning();
    }
  }
});
document.addEventListener("keyup", function (event) {
  if (isGameOver) return;
  if (event.code === "ControlLeft") {
    isDucking = false;
    dino.classList.remove("dino-ducking");
    animateRunning();
  }
});

// eventlistener per il pulsante di muto
muteBtn.addEventListener("click", function () {
  isMuted = !isMuted;

  if (isMuted) {
    muteBtn.style.backgroundImage = "url('assets/img/audioOff.png')";
  } else {
    muteBtn.style.backgroundImage = "url('assets/img/audioOn.png')";
  }
  this.blur();
});

/* Funzioni */
function startGameLoop() {
  gameLoopId = setInterval(() => {
    if (isGameOver) {
      clearInterval(gameLoopId);
      return;
    }
    dinoTop = Number.parseInt(
      globalThis.getComputedStyle(dino).getPropertyValue("top"),
    );
  }, 20); // Ogni 20ms
}

function animateRunning() {
  if (isGameOver) return;

  // Se siamo abbassati, usiamo le sprite duck
  if (!isDucking) {
    if (isRightFoot) {
      dino.style.backgroundImage = "url('assets/img/dino_l.png')";
      isRightFoot = false;
    } else {
      dino.style.backgroundImage = "url('assets/img/dino_r.png')";
      isRightFoot = true;
    }
  } else if (isRightFoot) {
    // alterno le due png in cache
    dino.style.backgroundImage = "url('assets/img/duck_l.png')";
    isRightFoot = false;
  } else {
    dino.style.backgroundImage = "url('assets/img/duck_r.png')";
    isRightFoot = true;
  }
}
runAnimationId = setInterval(animateRunning, 100); // ogni 100ms

function jump() {
  if (!dino.classList.contains("animate-jump")) {
    dino.classList.add("animate-jump");
    if (!isMuted) {
      jumpSound.currentTime = 0;
      jumpSound.play();
    }
    setTimeout(() => {
      dino.classList.remove("animate-jump");
    }, 500);
  }
}

// funzione per il punteggio
function updateScore(points) {
  score += points;
  scoreElement.innerHTML = "Punteggio: " + score;

  if (score > 0 && score % 100 === 0) {
    // ogni 100 pt aggiungo una animazione per il punteggio
    scoreElement.classList.add("blink-animation");
    if (!isMuted) {
      pointSound.currentTime = 0;
      pointSound.play();
    }
    setTimeout(() => {
      scoreElement.classList.remove("blink-animation");
    }, 1200);
  }
}

// manager degli ostacoli
function spawnObstacle() {
  if (isGameOver) return;
  if (score > 200 && Math.random() < 0.15) {
    generatePterodactyl();
  } else {
    generateCactus();
  }

  // aumento la difficolta' man mano che si va avanti
  let difficultyFactor = Math.min(score * 2, 800); // La difficolta' massima si raggiunge quando il punteggio e' 800
  let minTime = 1000 - difficultyFactor * 0.5; // Calcolo il tempo di spawn fra un ostacolo e l'altro
  if (minTime < 600) minTime = 600; // Il tempo minimo di spawn e' 600

  let variance = 1500 - difficultyFactor; // La varianza e' il fattore che genera una casualita' fra lo spawn di ogni ostacolo
  if (variance < 400) variance = 400; // Varianza minima 400

  let randomTime = Math.random() * variance + minTime; // Tempo di spawn finale
  setTimeout(spawnObstacle, randomTime); // Imposto quindi il tempo di attesa fra lo spawn di due ostacoli
}

// funzione per i cactus
function generateCactus() {
  let cactus = document.createElement("div");
  cactus.classList.add("cactus");

  let obstacleHeight = 0;
  let obstacleWidth = 0;

  // Scelgo quale tipo di cactus generare
  if (Math.random() < 0.5) {
    cactus.classList.add("cactus-group");

    obstacleWidth = 70;
    obstacleHeight = 75;
  } else {
    cactus.classList.add("cactus-single");

    obstacleWidth = 65;
    obstacleHeight = 80;
  }
  gameContainer.appendChild(cactus);

  // Calcolo la posizione del cactus
  let cactusPosition = 900;
  cactus.style.left = cactusPosition + "px";
  let currentSpeed = 10 + Math.floor(score / 100);

  let timerId = setInterval(function () {
    // se la partita e' finita
    if (isGameOver) {
      clearInterval(timerId);
      return;
    }

    // Sposto il cactus verso il player
    cactusPosition -= currentSpeed;
    cactus.style.left = cactusPosition + "px";

    // Verifico se l'ho colpito
    let collisionThreshold = 320 - obstacleHeight; // 320 e' il livello del 'terreno' - Altezza del dinosauro, e' legato al .css
    if (
      cactusPosition > 0 && // Se non e' ancora superato
      cactusPosition < obstacleWidth && // Se e' in una posizione di hit
      dinoTop >= collisionThreshold // Se non ho saltato
    ) {
      gameOver();
      clearInterval(timerId);
    }

    // Se supera la soglia lo despawno
    if (cactusPosition < -100) {
      clearInterval(timerId);
      if (gameContainer.contains(cactus)) cactus.remove();
      updateScore(10); // aumento il punteggio di 10 per ogni cactus superato
    }
  }, 20); // ogni 20ms eseguo la funzione
}

function checkPCollision(p, h) {
  if (p > 0 && p < 60) {
    switch (h) {
      case 10: // Ptero Basso -> Devo saltare
        if (dinoTop > 300) {
          return true;
        }
        break;
      case 35: // Ptero Medio -> Devo saltare o accovacciarmi
        if (dinoTop < 330 && dinoTop > 260) {
          return true;
        }
        break;
      case 120: // Ptero Alto -> Non devo saltare
        if (dinoTop < 250) {
          return true;
        }
        break;
    }
  }
  return false;
}

function generatePterodactyl() {
  let ptero = document.createElement("div");
  ptero.classList.add("pterodactyl");
  gameContainer.appendChild(ptero);

  let pteroPosition = 850;
  ptero.style.left = pteroPosition + "px";

  // altezza randomica
  let pteroHeights = [120, 35, 10]; // tre altezze possibili
  let randomHeight =
    pteroHeights[Math.floor(Math.random() * pteroHeights.length)]; // ne scelgo una a caso
  ptero.style.bottom = randomHeight + "px";

  let currentSpeed = (10 + Math.floor(score / 100)) * 1.5; // piu' veloce dei cactus

  let timerId = setInterval(function () {
    if (isGameOver) {
      clearInterval(timerId);
      return;
    }

    pteroPosition -= currentSpeed;
    ptero.style.left = pteroPosition + "px";

    let collision = checkPCollision(pteroPosition, randomHeight);
    if (collision) {
      gameOver();
      clearInterval(timerId);
    }
    if (pteroPosition < -60) {
      clearInterval(timerId);
      if (gameContainer.contains(ptero)) ptero.remove();
      updateScore(20); // aumento il punteggio di 20 per ogni pterodattilo superato
    }
  }, 20);
}

// --- GAME OVER ---
function gameOver() {
  isGameOver = true;
  if (!isMuted) {
    deathSound.currentTime = 0;
    deathSound.play();
  }
  dino.classList.remove("dino-ducking"); // se crouchato elimino la classe per evitare ridimensionamenti
  dino.style.backgroundImage = "url('assets/img/dino_dead.png')";
  clearInterval(runAnimationId);

  finalScoreSpan.innerText = score;
  gameOverMsg.style.display = "block";

  setTimeout(function () {
    sendScoreToDatabase(score);
  }, 3000); // Aspetto 3 secondi
}

// Nascondo il punteggio in un form invisibile all'utente POST
function sendScoreToDatabase(scoreValue) {
  const form = document.createElement("form");
  form.method = "POST";
  form.action = "../database/saveGame.php";

  const inputScore = document.createElement("input");
  inputScore.type = "hidden";
  inputScore.name = "score";
  inputScore.value = scoreValue;

  form.appendChild(inputScore);
  document.body.appendChild(form);

  form.submit();
}
