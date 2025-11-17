<?php require_once "api/db.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Parking Dashboard</title>

<!-- QR Scanner Library -->
<script src="https://unpkg.com/html5-qrcode"></script>

<style>
body {
  font-family: Arial, sans-serif;
  background:#e3f2fd;
  margin:0;
  padding:0;
}

/* HEADER */
header {
  position:fixed;
  top:0; left:0;
  width:100%;
  background:#1976d2;
  color:white;
  padding:12px 20px;
  z-index:1000;
  box-shadow:0 2px 6px rgba(0,0,0,0.2);
}
.header-title {
  font-size:18px;
  font-weight:bold;
  text-align:center;
  margin-bottom:5px;
}
nav {
  display:flex;
  gap:8px;
  justify-content:center;
  flex-wrap:wrap;
}
nav button {
  background:#42a5f5;
  color:white;
  border:none;
  padding:8px 12px;
  border-radius:6px;
  cursor:pointer;
}
nav button.active, nav button:hover {
  background:#0d47a1;
}

/* SECTIONS */
.section, .container {
  max-width:1100px;
  margin:120px auto 20px;
  background:white;
  padding:20px;
  border-radius:10px;
  box-shadow:0 0 10px rgba(0,0,0,0.12);
}
h3 {
  text-align:center;
  color:#1976d2;
}

/* FLOOR GRID */
.floor-layout {
  display:flex;
  gap:12px;
  flex-wrap:wrap;
  justify-content:space-between;
}
.zone-card {
  flex:1 1 30%;
  min-width:260px;
  background:#fafafa;
  border-radius:8px;
  padding:12px;
  border:1px solid #e0e0e0;
}
.zoneGrid {
  display:grid;
  grid-template-columns:repeat(5,1fr);
  gap:6px;
}
.slot {
  padding:8px;
  border-radius:6px;
  text-align:center;
  font-weight:bold;
  border:1px solid #ccc;
  user-select:none;
}
.available { background:#a5d6a7; }
.occupied { background:#ef9a9a; }

/* LEGEND */
.legend {
  display:flex;
  gap:20px;
  justify-content:center;
  margin-bottom:10px;
  font-size:14px;
}
.legend-item {
  display:flex;
  align-items:center;
  gap:6px;
}
.legend-box {
  width:18px;
  height:18px;
  border-radius:4px;
  border:1px solid #666;
}
.legend-available { background:#a5d6a7; }
.legend-occupied { background:#ef9a9a; }

/* LOGS */
.logs {
  max-height:400px;
  overflow-y:auto;
  background:#e3f2fd;
  border:1px solid #90caf9;
  padding:10px;
  border-radius:8px;
  font-size:14px;
}

/* LOGIN */
#loginPage {
  position:fixed;
  top:50%; left:50%;
  transform:translate(-50%, -50%);
  width:380px;
  background:white;
  padding:20px;
  border-radius:10px;
  text-align:center;
  box-shadow:0 0 10px rgba(0,0,0,0.2);
}
#loginPage input {
  width:90%;
  padding:10px;
  margin:8px 0;
  border-radius:5px;
  border:1px solid #90caf9;
}
#loginPage button {
  width:95%;
  background:#2196f3;
  color:white;
  border:none;
  padding:10px;
  border-radius:5px;
  cursor:pointer;
}

.hidden { display:none; }

/* FOOTER */
footer {
  text-align:center;
  font-size:13px;
  color:#555;
  padding:10px;
  background:#f8f9fa;
  border-top:1px solid #ccc;
  position:fixed;
  bottom:0;
  left:0;
  width:100%;
  z-index:10;
}

/* SCANNER */
#reader {
  width:330px;
  margin:0 auto;
  border-radius:12px;
  overflow:hidden;
  box-shadow:0 0 8px rgba(0,0,0,0.2);
}
#infoBox {
  display:none;
  background:#e3f2fd;
  padding:15px;
  margin-top:18px;
  border-radius:10px;
  box-shadow:0 0 5px rgba(0,0,0,0.25);
}

/* NEW CAMERA BUTTON STYLES */
.btn-active {
  opacity: 1 !important;
  box-shadow: 0 0 10px rgba(0,0,0,0.2);
  transform: scale(1.03);
}

.btn-disabled {
  opacity: 0.5;
  cursor: not-allowed !important;
}
</style>
</head>
<body>

<!-- LOGIN SCREEN -->
<div id="loginPage">
  <h2 style="color:#1976d2;">Security Login</h2>
  <input type="text" id="securityID" placeholder="Enter Security ID">
  <input type="password" id="password" placeholder="Enter Password">
  <button onclick="login()">Log in</button>
</div>

<!-- DASHBOARD -->
<div id="dashboard" class="hidden">

  <header>
    <div class="header-title">Smart Parking System - Security Dashboard</div>

    <nav>
      <button id="btn1" onclick="showSection('floor1')">Floor 1</button>
      <button id="btn2" onclick="showSection('floor2')">Floor 2</button>
      <button id="btnEntry" onclick="showSection('entryLog')">Entry Log</button>
      <button id="btnExit" onclick="showSection('exitLog')">Exit Log</button>
      <button id="btnScanner" onclick="showSection('scannerPage')">Scanner</button>
      <button id="btnUser" onclick="showSection('userInfo')">Profile</button>
    </nav>
  </header>

  <!-- FLOOR 1 -->
  <div id="floor1" class="container section">
    <h3>Floor 1 — Zones</h3>

    <div class="legend">
      <div class="legend-item"><div class="legend-box legend-available"></div> Available</div>
      <div class="legend-item"><div class="legend-box legend-occupied"></div> Occupied</div>
    </div>

    <div class="floor-layout">
      <div class="zone-card"><h4>Zone A — Car</h4><div id="F1A" class="zoneGrid"></div></div>
      <div class="zone-card"><h4>Zone B — Motorcycle</h4><div id="F1B" class="zoneGrid"></div></div>
      <div class="zone-card"><h4>Zone C — Truck</h4><div id="F1C" class="zoneGrid"></div></div>
    </div>
  </div>

  <!-- FLOOR 2 -->
  <div id="floor2" class="container section hidden">
    <h3>Floor 2 — Zones</h3>

    <div class="legend">
      <div class="legend-item"><div class="legend-box legend-available"></div> Available</div>
      <div class="legend-item"><div class="legend-box legend-occupied"></div> Occupied</div>
    </div>

    <div class="floor-layout">
      <div class="zone-card"><h4>Zone A — Car</h4><div id="F2A" class="zoneGrid"></div></div>
      <div class="zone-card"><h4>Zone B — Motorcycle</h4><div id="F2B" class="zoneGrid"></div></div>
      <div class="zone-card"><h4>Zone C — Truck</h4><div id="F2C" class="zoneGrid"></div></div>
    </div>
  </div>

  <!-- ENTRY LOG -->
  <div id="entryLog" class="container section hidden">
    <h3>Entry Log</h3>
    <div id="entryLogs" class="logs"></div>
  </div>

  <!-- EXIT LOG -->
  <div id="exitLog" class="container section hidden">
    <h3>Exit Log</h3>
    <div id="exitLogs" class="logs"></div>
  </div>

  <!-- SCANNER SECTION -->
  <div id="scannerPage" class="container section hidden">
    <h3>QR Scanner</h3>

    <div style="text-align:center; margin-bottom:15px;">
      <button id="btnOpenCam"
        onclick="startCamera()"
        style="background:#1976d2; color:white; padding:10px 18px; border:none; border-radius:6px;">
        Open Camera
      </button>

      <button id="btnCloseCam"
        onclick="stopCamera()"
        class="btn-disabled"
        style="background:#b71c1c; color:white; padding:10px 18px; border:none; border-radius:6px;">
        Close Camera
      </button>
    </div>

    <div id="reader"></div>

    <div id="infoBox">
      <p><b>Ticket:</b> <span id="ti"></span></p>
      <p><b>Owner:</b> <span id="ow"></span></p>
      <p><b>Plate:</b> <span id="pl"></span></p>
      <p><b>Vehicle:</b> <span id="vt"></span></p>
      <p><b>Floor:</b> <span id="fl"></span></p>
      <p><b>Zone:</b> <span id="zc"></span> &nbsp;&nbsp; <b>Slot:</b> <span id="sn"></span></p>

      <div style="text-align:center; margin-top:15px;">
        <button onclick="sendEvent('entry')" style="background:#1565c0; padding:10px 18px; color:white; border:none; border-radius:6px; margin-right:8px;">Entry</button>
        <button onclick="sendEvent('exit')" style="background:#2e7d32; padding:10px 18px; color:white; border:none; border-radius:6px;">Exit</button>
      </div>
    </div>
  </div>

  <!-- PROFILE PAGE -->
  <div id="userInfo" class="container section hidden">
    <h3>Profile</h3>
    <p><b>ID:</b> <span id="infoID"></span></p>
    <p><b>Name:</b> <span id="infoName"></span></p>
    <p><b>Access Level:</b> Security Personnel</p>
    <button onclick="logout()">Logout</button>
  </div>
</div>

<footer>© 2025 Smart Parking System</footer>

<script>
// Security accounts
const ACCOUNTS = {
  "RS01-8990": { name:"Rogers, Steve", password:"captain123" },
  "BB02-1997": { name:"Barnes, Bucky", password:"winter456" },
  "ST03-5521": { name:"Stark, Tony", password:"iron789" },
  "RM04-1109": { name:"Romanoff, Natasha", password:"hawk999" }
};

let currentUser = null;
let refreshTimer = null;

/******** LOGIN ********/
function login() {
  const id = document.getElementById("securityID").value.trim();
  const pw = document.getElementById("password").value.trim();

  if (ACCOUNTS[id] && ACCOUNTS[id].password === pw) {
    currentUser = { id, name: ACCOUNTS[id].name };

    document.getElementById("loginPage").classList.add("hidden");
    document.getElementById("dashboard").classList.remove("hidden");

    document.getElementById("infoID").innerText = id;
    document.getElementById("infoName").innerText = currentUser.name;

    showSection("floor1");
    updateDashboard();
    refreshTimer = setInterval(updateDashboard, 3000);
  } else {
    alert("Invalid Security ID or Password");
  }
}

/******** LOGOUT ********/
function logout() {
  currentUser = null;
  clearInterval(refreshTimer);
  stopCamera();

  document.getElementById("dashboard").classList.add("hidden");
  document.getElementById("loginPage").classList.remove("hidden");

  document.getElementById("securityID").value = "";
  document.getElementById("password").value = "";

  alert("Logged out successfully.");
}

/******** NAVIGATION ********/
function showSection(id) {
  document.querySelectorAll(".section").forEach(s => s.classList.add("hidden"));
  document.getElementById(id).classList.remove("hidden");

  document.querySelectorAll("nav button").forEach(b => b.classList.remove("active"));

  if (id === "floor1") document.getElementById("btn1").classList.add("active");
  else if (id === "floor2") document.getElementById("btn2").classList.add("active");
  else if (id === "entryLog") document.getElementById("btnEntry").classList.add("active");
  else if (id === "exitLog") document.getElementById("btnExit").classList.add("active");
  else if (id === "scannerPage") document.getElementById("btnScanner").classList.add("active");
  else document.getElementById("btnUser").classList.add("active");

  if (id !== "scannerPage") stopCamera();
}

/******** DASHBOARD REFRESH ********/
async function updateDashboard() {
  try {
    const [ticketsRes, logsRes] = await Promise.all([
      fetch("api/tickets.php"),
      fetch("api/logs.php")
    ]);

    const tickets = await ticketsRes.json();
    const logs = await logsRes.json();

    renderFloor(tickets, 1);
    renderFloor(tickets, 2);
    renderLogs(logs);

  } catch (err) {
    console.error("Dashboard update failed:", err);
  }
}

/******** RENDER FLOOR ********/
function renderFloor(tickets, floorNum) {
  const floorStr = `Floor ${floorNum}`;

  ["A","B","C"].forEach(zone => {
    const containerID = `F${floorNum}${zone}`;
    const container = document.getElementById(containerID);

    container.innerHTML = "";

    const takenSlots = tickets
      .filter(t => t.floor === floorStr && t.zone_code === zone && t.active == 1)
      .map(t => parseInt(t.slot_number));

    for (let s = 1; s <= 10; s++) {
      const slot = document.createElement("div");
      slot.className = "slot " + (takenSlots.includes(s) ? "occupied" : "available");
      slot.textContent = s;
      container.appendChild(slot);
    }
  });
}

/******** RENDER LOGS ********/
function renderLogs(logs) {
  const entryDiv = document.getElementById("entryLogs");
  const exitDiv  = document.getElementById("exitLogs");

  entryDiv.innerHTML = "";
  exitDiv.innerHTML = "";

  logs.forEach(l => {
    const line = `[${l.created_at}] ${l.ticket_uid} — ${l.owner_snapshot} (${l.plate_snapshot}) — ${l.floor_snapshot} / ${l.zone_snapshot}`;
    if (l.event_type === "entry") entryDiv.innerHTML += line + "<br>";
    else exitDiv.innerHTML += line + "<br>";
  });
}

/******** QR SCANNER ********/
let scanner = null;
let cameraOpen = false;
let lastPayload = null;

// Start camera
function startCamera() {
  if (cameraOpen) return;

  const btnOpen = document.querySelector("#btnOpenCam");
  const btnClose = document.querySelector("#btnCloseCam");

  btnOpen.classList.add("btn-active");
  btnClose.classList.remove("btn-disabled");

  scanner = new Html5Qrcode("reader");

  scanner.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: 250 },
    decoded => {
      try {
        const data = JSON.parse(decoded);
        showScannedInfo(data);
        lastPayload = data;
      } catch {
        lastPayload = { ticket_uid: decoded.trim() };
        showScannedInfo(lastPayload, true);
      }
    },
    err => {}
  );

  cameraOpen = true;
}

// Stop camera
function stopCamera() {
  if (!cameraOpen || !scanner) return;

  const btnOpen = document.querySelector("#btnOpenCam");
  const btnClose = document.querySelector("#btnCloseCam");

  btnOpen.classList.remove("btn-active");
  btnClose.classList.add("btn-disabled");

  scanner.stop().then(() => {
    cameraOpen = false;
    document.getElementById("reader").innerHTML = "";

    // Hide info box
    document.getElementById("infoBox").style.display = "none";

    // Reset ticket info
    ["ti","ow","pl","vt","fl","zc","sn"].forEach(id => {
      document.getElementById(id).innerText = "";
    });

    lastPayload = null;
  });
}

function showScannedInfo(data, minimal=false) {
  document.getElementById("infoBox").style.display = "block";

  document.getElementById("ti").innerText = data.ticket_uid || "N/A";
  document.getElementById("ow").innerText = minimal ? "N/A" : data.owner;
  document.getElementById("pl").innerText = minimal ? "N/A" : data.plate;
  document.getElementById("vt").innerText = minimal ? "N/A" : data.vtype;
  document.getElementById("fl").innerText = minimal ? "N/A" : data.floor;
  document.getElementById("zc").innerText = minimal ? "N/A" : data.zone_code;
  document.getElementById("sn").innerText = minimal ? "N/A" : data.slot_number;
}

async function sendEvent(type) {
  if (!lastPayload || !lastPayload.ticket_uid) {
    alert("Scan a valid QR first.");
    return;
  }

  if (!confirm(`Confirm mark ${type.toUpperCase()}?`)) return;

  const fd = new FormData();
  fd.append("ticket_uid", lastPayload.ticket_uid);
  fd.append("event_type", type);

  try {
    const res = await fetch("api/logs.php", { method:"POST", body:fd });
    const json = await res.json();

    if (json.success) {
      alert(`Marked as ${type.toUpperCase()}.`);
    } else {
      alert("Error: " + json.error);
    }
  } catch {
    alert("Connection error.");
  }
}

// Initial load
updateDashboard();
</script>

</body>
</html>
