const dino = document.getElementById("dino");
const gameContainer = document.querySelector(".game-container");
const scoreElement = document.getElementById("score");
const startMessage = document.getElementById("start-message");
const gameOverMsg = document.getElementById("game-over-msg");
const finalScoreSpan = document.getElementById("final-score");
const muteBtn = document.getElementById("mute-btn");

let score = 0; // variabile per memorizzare il punteggio
let isGameStarted = false;
let isGameOver = false;

// gestione audio
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

// gestione animazioni
let isRightFoot = true;
let runAnimationId;
let isDucking = false;

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

// eventlistener dello spazio per chiamare spawnObstacle
document.addEventListener("keydown", function (event) {
  if (isGameOver) return;
  if (event.code === "Space") {
    if (startMessage) startMessage.style.display = "none";

    if (!isGameStarted) {
      isGameStarted = true;
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

// funzione per il salto
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
  let difficultyFactor = Math.min(score * 2, 800);
  let minTime = 1000 - difficultyFactor * 0.5;
  if (minTime < 600) minTime = 600;

  let variance = 1500 - difficultyFactor;
  if (variance < 400) variance = 400;

  let randomTime = Math.random() * variance + minTime;
  setTimeout(spawnObstacle, randomTime);
}

// funzione per i cactus
function generateCactus() {
  let cactus = document.createElement("div");
  cactus.classList.add("cactus");

  let obstacleHeight = 0;
  let obstacleWidth = 0;

  if (Math.random() < 0.5) {
    cactus.classList.add("cactus-group");

    obstacleWidth = 100;
    obstacleHeight = 85;
  } else {
    cactus.classList.add("cactus-single");

    obstacleWidth = 50;
    obstacleHeight = 90;
  }
  gameContainer.appendChild(cactus);

  let cactusPosition = 900;
  cactus.style.left = cactusPosition + "px";
  let currentSpeed = 10 + Math.floor(score / 100);

  let timerId = setInterval(function () {
    // se la partita e' finita
    if (isGameOver) {
      clearInterval(timerId);
      return;
    }

    cactusPosition -= currentSpeed;
    cactus.style.left = cactusPosition + "px";

    let dinoTop = Number.parseInt(
      globalThis.getComputedStyle(dino).getPropertyValue("top")
    );
    let collisionThreshold = 320 - obstacleHeight + 20;
    let DIFF = 10; // fattore di difficolta'
    if (
      cactusPosition > DIFF &&
      cactusPosition < obstacleWidth - DIFF &&
      dinoTop >= collisionThreshold
    ) {
      gameOver();
      clearInterval(timerId);
    }

    if (cactusPosition < -100) {
      clearInterval(timerId);
      if (gameContainer.contains(cactus)) cactus.remove();
      updateScore(10); // aumento il punteggio di 10 per ogni cactus superato
    }
  }, 20); // ogni 20ms eseguo la funzione
}

function generatePterodactyl() {
  let ptero = document.createElement("div");
  ptero.classList.add("pterodactyl");
  gameContainer.appendChild(ptero);

  let pteroPosition = 850;
  ptero.style.left = pteroPosition + "px";
  let currentSpeed = (10 + Math.floor(score / 100)) * 1.3;

  let timerId = setInterval(function () {
    if (isGameOver) {
      clearInterval(timerId);
      return;
    }

    pteroPosition -= currentSpeed;
    ptero.style.left = pteroPosition + "px";

    let dinoTop = Number.parseInt(
      globalThis.getComputedStyle(dino).getPropertyValue("top")
    );

    if (pteroPosition > 0 && pteroPosition < 60) {
      if (dinoTop >= 260 && !isDucking) {
        gameOver();
        clearInterval(timerId);
      }
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
  dino.style.backgroundImage = "url('assets/img/dino_dead.png')"; // png dino morto
  clearInterval(runAnimationId);

  finalScoreSpan.innerText = score;
  gameOverMsg.style.display = "block";

  setTimeout(function () {
    globalThis.location.href = "../database/saveGame.php?score=" + score; // memorizzo lo score nell'header, lo recupero con GET
  }, 3000);
}
