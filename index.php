<?php
// Simple DB connection demo for entry page
$host = "localhost";
$user = "root";
$password = ""; // your DB password
$dbname = "habit_tracker";

$conn = new mysqli($host, $user, $password, $dbname);
$db_status = "";

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Habit Tracker - Welcome</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-cover bg-center min-h-screen flex items-center justify-center" style="background-image: url('img4.jpg');"></body>

  <div class="bg-gray-300 p-8 rounded-lg shadow-lg max-w-md w-full text-center text-gray-800">

    <h1 class="text-4xl font-bold mb-6 text-gray-800">Welcome to Habit Tracker</h1>

    <p class="mb-4 text-gray-600"><?= htmlspecialchars($db_status) ?></p>

    <div class="space-y-4">
      <a href="login.php" class="block bg-blue-600 text-white py-3 rounded hover:bg-blue-700 transition">
        Login
      </a>
      <a href="regi.php" class="block bg-green-600 text-white py-3 rounded hover:bg-green-700 transition">
        Register
      </a>
    </div>

    <p class="mt-6 text-gray-500 text-sm">Track your daily habits, stay productive!</p>
  </div>

</body>
</html>
