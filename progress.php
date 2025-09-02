<?php
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

// --- Calculate date 7 days ago ---
$start_date = date("Y-m-d", strtotime("-6 days"));

// --- Fetch habit done counts per habit in last 7 days ---
$stmt = $conn->prepare("
    SELECT name, COUNT(*) as done_count
    FROM habit_done_log
    WHERE user_id = ? AND done_date >= ?
    GROUP BY name
    ORDER BY done_count DESC
");
$stmt->bind_param("is", $user_id, $start_date);
$stmt->execute();
$result = $stmt->get_result();

$habits = [];
while ($row = $result->fetch_assoc()) {
    $habits[] = $row;
}
$stmt->close();

$max_possible = 7; // last 7 days
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Habit Progress</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-cover bg-center min-h-screen flex items-start justify-center pt-12" style="background-image: url('img11.jpg');">


  <div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6 px-12">
      <h1 class="text-3xl font-bold text-gray-800">📈 Habit Progress </h1>
       <a href="dashboard.php" class="ml-14 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">← Dashboard</a>

    </div>

    <div class="bg-white p-6 rounded shadow space-y-4">
      <?php if (empty($habits)): ?>
        <p class="text-gray-500">No habit completions recorded in the last 7 days.</p>
      <?php else: ?>
        <?php foreach ($habits as $habit): 
          // Calculate progress percentage (max 7)
          $widthPercent = ($habit['done_count'] / $max_possible) * 100;
          if ($widthPercent > 100) $widthPercent = 100; // cap at 100%
        ?>
          <div>
            <div class="flex justify-between mb-1 font-semibold text-gray-700">
              <span><?= htmlspecialchars($habit['name']) ?></span>
              <span><?= $habit['done_count'] ?>/<?= $max_possible ?> times</span>
            </div>
            <div class="w-full bg-gray-300 rounded h-6">
              <div class="bg-blue-600 h-6 rounded" style="width: <?= $widthPercent ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
