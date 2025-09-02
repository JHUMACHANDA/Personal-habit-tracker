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

// --- Fetch Done Logs Grouped by Date ---
// Now select habit name directly from habit_done_log.name column
$stmt = $conn->prepare("
    SELECT done_date, name
    FROM habit_done_log
    WHERE user_id = ?
    ORDER BY done_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$history = [];
while ($row = $res->fetch_assoc()) {
    $history[$row['done_date']][] = $row['name'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Habit History</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-cover bg-center min-h-screen flex items-start justify-center pt-12" style="background-image: url('img12.jpg');">

  <div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6 px-12">
      <h1 class="text-3xl font-bold text-gray-800">📜 Habit History</h1>
        <a href="dashboard.php" class="ml-14 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">← Dashboard</a>

    </div>

    <?php if (empty($history)): ?>
      <p class="text-gray-500">No habit history yet.</p>
    <?php else: ?>
      <?php foreach ($history as $date => $habits): ?>
        <div class="bg-white p-4 rounded shadow mb-4">
          <h2 class="text-xl font-semibold mb-2"><?= htmlspecialchars($date) ?></h2>
          <ul class="list-disc list-inside text-gray-700">
            <?php foreach ($habits as $habit): ?>
              <li><?= htmlspecialchars($habit) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>
