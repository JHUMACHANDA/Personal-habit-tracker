// script.js

// Load habits from localStorage or initialize with default habits
let habits = JSON.parse(localStorage.getItem("habits")) || [
  { name: "Morning Walk", completedDays: [] },
  { name: "Read Book", completedDays: [] },
  { name: "Drink Water", completedDays: [] }
];

// Save habits to localStorage
function saveHabits() {
  localStorage.setItem("habits", JSON.stringify(habits));
}

// Login and Register simulation
function login() {
  alert("Logged in!");
  window.location.href = "dashboard.html";
}

function register() {
  alert("Registered!");
  window.location.href = "index.html";
}

// Add a new habit from the dashboard
function addHabit() {
  const input = document.getElementById("new-habit");
  const habitName = input.value.trim();

  if (habitName === "") {
    alert("Please enter a habit name.");
    return;
  }

  // Prevent duplicate habits (optional)
  const exists = habits.some(habit => habit.name.toLowerCase() === habitName.toLowerCase());
  if (exists) {
    alert("This habit already exists.");
    return;
  }

  habits.push({ name: habitName, completedDays: [] });
  saveHabits();
  input.value = ""; // Clear input
  loadDashboard(); // Reload list
}

// Load the dashboard with all habits
function loadDashboard() {
  const list = document.getElementById("habit-list");
  if (!list) return;

  list.innerHTML = ""; // Clear previous list

  habits.forEach((habit, index) => {
    const today = new Date().toISOString().split("T")[0];
    const doneToday = habit.completedDays.includes(today);

    const div = document.createElement("div");
    div.className = "p-4 bg-white shadow rounded flex justify-between items-center";
    div.innerHTML = `
      <div>${habit.name}</div>
      <button class="px-3 py-1 text-sm ${doneToday ? 'bg-green-400' : 'bg-blue-500'} text-white rounded" onclick="markDone(${index})">
        ${doneToday ? 'Done' : 'Mark as Done'}
      </button>
    `;
    list.appendChild(div);
  });
}

// Mark a habit as done for today
function markDone(index) {
  const today = new Date().toISOString().split("T")[0];
  if (!habits[index].completedDays.includes(today)) {
    habits[index].completedDays.push(today);
    saveHabits();
    loadDashboard();
  }
}

// Load the habit history
function loadHistory() {
  const ul = document.getElementById("history-list");
  if (!ul) return;

  habits.forEach(habit => {
    const li = document.createElement("li");
    li.innerHTML = `<strong>${habit.name}</strong>: ${habit.completedDays.join(", ") || "No history"}`;
    ul.appendChild(li);
  });
}

// Load the progress for the current week
function loadProgress() {
  const container = document.getElementById("progress-container");
  if (!container) return;

  const weekStart = new Date();
  weekStart.setDate(weekStart.getDate() - 6);

  habits.forEach(habit => {
    const count = habit.completedDays.filter(date => {
      const d = new Date(date);
      return d >= weekStart && d <= new Date();
    }).length;

    const progress = Math.floor((count / 7) * 100);

    const bar = document.createElement("div");
    bar.className = "mb-4";
    bar.innerHTML = `
      <div class="font-semibold">${habit.name}</div>
      <div class="w-full bg-gray-300 rounded-full h-4">
        <div class="bg-green-500 h-4 rounded-full" style="width: ${progress}%"></div>
      </div>
      <p class="text-sm text-gray-600">${count}/7 days this week</p>
    `;
    container.appendChild(bar);
  });
}

// Load the appropriate page elements
document.addEventListener("DOMContentLoaded", () => {
  loadDashboard();
  loadHistory();
  loadProgress();
});
