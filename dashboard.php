<?php
// Set the timezone explicitly to your timezone
date_default_timezone_set('Asia/Dhaka');  // change as needed

session_start();

// --- DB Connection ---
$host = "localhost";
$user = "root";
$password = "";
$dbname = "habit_tracker";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// --- Auth Check ---
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"] ?? "User";
$today = date("Y-m-d");  // Now respects the timezone set above

// --- Default Habits Setup ---
$default_habits = ["Prayers", "Drink Water", "Exercise", "Enough Sleep"];
foreach ($default_habits as $habit) {
    // Check if in user_habits
    $stmt = $conn->prepare("SELECT id FROM user_habits WHERE user_id = ? AND name = ? AND type = 'default'");
    $stmt->bind_param("is", $user_id, $habit);
    $stmt->execute();
    $stmt->store_result();
    $in_habits = $stmt->num_rows > 0;
    $stmt->close();

    // Check if in hidden_habits
    $stmt = $conn->prepare("SELECT id FROM hidden_habits WHERE user_id = ? AND habit_name = ?");
    $stmt->bind_param("is", $user_id, $habit);
    $stmt->execute();
    $stmt->store_result();
    $in_hidden = $stmt->num_rows > 0;
    $stmt->close();

    // Insert default habit if missing and not hidden
    if (!$in_habits && !$in_hidden) {
        $stmt = $conn->prepare("INSERT INTO user_habits (user_id, name, type) VALUES (?, ?, 'default')");
        $stmt->bind_param("is", $user_id, $habit);
        $stmt->execute();
        $stmt->close();
    }
}

// --- Delete Habit ---
if (isset($_GET["delete"])) {
    $habit_id = intval($_GET["delete"]);

    $stmt = $conn->prepare("SELECT name, type FROM user_habits WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $habit_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($habit_name, $habit_type);
    if ($stmt->fetch()) {
        $stmt->close();

        if ($habit_type === 'default') {
            // Add to hidden_habits so default doesn't reappear
            $stmt = $conn->prepare("INSERT INTO hidden_habits (user_id, habit_name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $habit_name);
            $stmt->execute();
            $stmt->close();
        }

        // Delete from user_habits
        $stmt = $conn->prepare("DELETE FROM user_habits WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $habit_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
    }

    header("Location: dashboard.php");
    exit();
}

// --- Mark Default Habit as Done ---
if (isset($_GET['mark_default'])) {
    $habit_name = $_GET['mark_default'];

    $stmt = $conn->prepare("SELECT id FROM user_habits WHERE user_id = ? AND name = ? AND type = 'default'");
    $stmt->bind_param("is", $user_id, $habit_name);
    $stmt->execute();
    $stmt->bind_result($habit_id);
    if ($stmt->fetch()) {
        $stmt->close();

        // Check duplicate before insert
        $check = $conn->prepare("SELECT id FROM habit_done_log WHERE habit_id = ? AND user_id = ? AND done_date = ?");
        $check->bind_param("iis", $habit_id, $user_id, $today);
        $check->execute();
        $check->store_result();
        $already_done = $check->num_rows > 0;
        $check->close();

        if (!$already_done) {
            $insert = $conn->prepare("INSERT INTO habit_done_log (habit_id, name, done_date, user_id) VALUES (?, ?, ?, ?)");
            $insert->bind_param("issi", $habit_id, $habit_name, $today, $user_id);
            $insert->execute();
            $insert->close();
        }
    } else {
        $stmt->close();
    }

    header("Location: dashboard.php");
    exit();
}

// --- Mark Custom Habit as Done ---
if (isset($_GET["done"])) {
    $habit_id = intval($_GET["done"]);

    $stmt = $conn->prepare("SELECT name FROM user_habits WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $habit_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($habit_name);
    if ($stmt->fetch()) {
        $stmt->close();

        // Check duplicate before insert
        $check = $conn->prepare("SELECT id FROM habit_done_log WHERE habit_id = ? AND user_id = ? AND done_date = ?");
        $check->bind_param("iis", $habit_id, $user_id, $today);
        $check->execute();
        $check->store_result();
        $already_done = $check->num_rows > 0;
        $check->close();

        if (!$already_done) {
            $insert = $conn->prepare("INSERT INTO habit_done_log (habit_id, name, done_date, user_id) VALUES (?, ?, ?, ?)");
            $insert->bind_param("issi", $habit_id, $habit_name, $today, $user_id);
            $insert->execute();
            $insert->close();
        }
    } else {
        $stmt->close();
    }

    header("Location: dashboard.php");
    exit();
}

// --- Add Custom Habit ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["habit"])) {
    $habitName = trim($_POST["habit"]);
    if ($habitName !== "") {
        // Check if already exists to avoid duplicates
        $stmt = $conn->prepare("SELECT id FROM user_habits WHERE user_id = ? AND name = ?");
        $stmt->bind_param("is", $user_id, $habitName);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO user_habits (user_id, name, type) VALUES (?, ?, 'custom')");
            $stmt->bind_param("is", $user_id, $habitName);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt->close();
        }
    }
    header("Location: dashboard.php");
    exit();
}

// --- Fetch Default Habits + done_today info ---
$stmt = $conn->prepare("
    SELECT uh.id, uh.name,
    EXISTS (
        SELECT 1 FROM habit_done_log hdl
        WHERE hdl.habit_id = uh.id AND hdl.done_date = ? AND hdl.user_id = ?
    ) AS done_today
    FROM user_habits uh
    WHERE uh.user_id = ? AND uh.type = 'default'
");
$stmt->bind_param("sii", $today, $user_id, $user_id);
$stmt->execute();
$existing_default_habits = $stmt->get_result();
$stmt->close();

// --- Fetch Custom Habits + done_today info ---
$stmt = $conn->prepare("
    SELECT uh.id, uh.name,
    EXISTS (
        SELECT 1 FROM habit_done_log hdl
        WHERE hdl.habit_id = uh.id AND hdl.done_date = ? AND hdl.user_id = ?
    ) AS done_today
    FROM user_habits uh
    WHERE uh.user_id = ? AND uh.type = 'custom'
");
$stmt->bind_param("sii", $today, $user_id, $user_id);
$stmt->execute();
$custom_habits = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-cover bg-center min-h-screen flex items-center justify-center" style="background-image: url('img8.jpg');"></body>
<div class="max-w-3xl mx-auto">
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">Welcome, <?= htmlspecialchars($username) ?>!</h1>
    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
  </div>

  <!-- Add Habit -->
  <div class="bg-white p-4 rounded shadow mb-6">
    <h2 class="text-xl font-semibold mb-2">Add New Habit</h2>
    <form method="POST" class="flex gap-3">
      <input name="habit" type="text" placeholder="Habit name" class="flex-1 p-2 border rounded" required />
      <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Add</button>
    </form>
  </div>

  <!-- Default Habits -->
  <div class="bg-white p-4 rounded shadow mb-6">
    <h2 class="text-xl font-semibold mb-4">Default Habits</h2>
    <ul class="space-y-3">
      <?php while ($habit = $existing_default_habits->fetch_assoc()): ?>
        <li class="flex justify-between items-center p-2 border rounded bg-gray-50">
          <div class="font-medium"><?= htmlspecialchars($habit['name']) ?></div>
          <div class="flex gap-2">
            <?php if ($habit['done_today']): ?>
              <span class="text-green-600 font-semibold">✔ Done Today</span>
            <?php else: ?>
              <a href="?mark_default=<?= urlencode($habit['name']) ?>" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Mark Done</a>
            <?php endif; ?>
            <a href="?delete=<?= $habit['id'] ?>" class="text-red-500 hover:underline" onclick="return confirm('Delete this habit?')">🗑 Delete</a>
          </div>
        </li>
      <?php endwhile; ?>
    </ul>
  </div>

  <!-- Custom Habits -->
  <div class="bg-white p-4 rounded shadow">
    <h2 class="text-xl font-semibold mb-4">Custom Habits</h2>
    <ul class="space-y-3">
      <?php while ($habit = $custom_habits->fetch_assoc()): ?>
        <li class="flex justify-between items-center p-2 border rounded bg-gray-50">
          <div class="font-medium"><?= htmlspecialchars($habit['name']) ?></div>
          <div class="flex gap-2">
            <?php if ($habit['done_today']): ?>
              <span class="text-green-600 font-semibold">✔ Done Today</span>
            <?php else: ?>
              <a href="?done=<?= $habit['id'] ?>" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">Mark Done</a>
            <?php endif; ?>
            <a href="?delete=<?= $habit['id'] ?>" class="text-red-500 hover:underline" onclick="return confirm('Delete this habit?')">🗑 Delete</a>
          </div>
        </li>
      <?php endwhile; ?>
    </ul>
  </div>

  <!-- Navigation Buttons -->
  <div class="mt-8 flex flex-wrap gap-4 justify-center">
    <a href="history.php" class="bg-blue-500 text-white px-5 py-2 rounded hover:bg-blue-600 transition">📜 View History</a>
    <a href="progress.php" class="bg-yellow-500 text-white px-5 py-2 rounded hover:bg-yellow-600 transition">📈 Track Progress</a>
    <a href="search.php" class="bg-gray-700 text-white px-5 py-2 rounded hover:bg-gray-800 transition">🧑‍💼 Search User</a>
  </div>
</div>
</body>
</html>
