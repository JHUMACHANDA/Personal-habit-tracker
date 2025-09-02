<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Search User Habit Records</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-cover bg-center min-h-screen flex items-start justify-center pt-12" style="background-image: url('img13.jpg');">

  <div class="max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-6 px-12">
      <h1 class="text-2xl font-bold text-gray-800">🔍 Search User Habit Records</h1>
        <a href="dashboard.php" class="ml-14 bg-blue-500 text-white px-5 py-2 rounded hover:bg-blue-600 transition">
        ← Dashboard
      </a>

    
    </div>

    <!-- Search Form -->
    <form method="GET" class="flex gap-2 mb-6">
      <input type="text" name="q" class="flex-1 p-2 border rounded" placeholder="Search by username..." required>
      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Search</button>
    </form>

    <?php
    if (isset($_GET['q']) && trim($_GET['q']) !== '') {
        $search = trim($_GET['q']);

        $conn = new mysqli("localhost", "root", "", "habit_tracker");
        if ($conn->connect_error) {
            echo "<p class='text-red-500'>Database connection failed.</p>";
        } else {
            // Search users by username (partial match)
            $stmt = $conn->prepare("SELECT id, username FROM users WHERE username LIKE ?");
            $like = "%$search%";
            $stmt->bind_param("s", $like);
            $stmt->execute();
            $users = $stmt->get_result();
            $stmt->close();

            if ($users->num_rows > 0) {
                while ($user = $users->fetch_assoc()) {
                    echo "<div class='bg-white p-4 rounded shadow mb-6'>";
                    echo "<h2 class='text-xl font-semibold mb-2'>👤 " . htmlspecialchars($user['username']) . "</h2>";

                    // Fetch all habit done logs joined with user_habits for this user ordered by done_date descending
                    $stmt2 = $conn->prepare("
                        SELECT uh.name, uh.type, hdl.done_date
                        FROM habit_done_log hdl
                        JOIN user_habits uh ON uh.id = hdl.habit_id
                        WHERE hdl.user_id = ?
                        ORDER BY hdl.done_date DESC
                    ");
                    $stmt2->bind_param("i", $user['id']);
                    $stmt2->execute();
                    $habits = $stmt2->get_result();

                    if ($habits->num_rows > 0) {
                        echo "<ul class='list-disc ml-6 space-y-1'>";
                        while ($habit = $habits->fetch_assoc()) {
                            $done_date = date('M d, Y', strtotime($habit['done_date']));
                            echo "<li><strong>" . htmlspecialchars($habit['name']) . "</strong> (" . htmlspecialchars($habit['type']) . ") - Done on: " . $done_date . "</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p class='text-gray-500'>No completed habits found.</p>";
                    }

                    $stmt2->close();
                    echo "</div>";
                }
            } else {
                echo "<p class='text-gray-600'>No users found matching '<strong>" . htmlspecialchars($search) . "</strong>'.</p>";
            }

            $conn->close();
        }
    }
    ?>
  </div>
</body>
</html>
