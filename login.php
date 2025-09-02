<?php
session_start();

// --- DB Connection ---
$host = "localhost";
$user = "root";
$password = ""; // Your DB password
$dbname = "habit_tracker";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$loginError = "";

// --- Handle Login Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($userId, $hashedPassword);

    if ($stmt->fetch()) {
        if (password_verify($password, $hashedPassword)) {
            $_SESSION["user_id"] = $userId;
            $_SESSION["username"] = $username;
            header("Location: dashboard.php");
            exit();
        } else {
            $loginError = "Incorrect password.";
        }
    } else {
        $loginError = "User not found.";
    }

    $stmt->close();
}
?>

<!-- ✅ Login Form UI -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-cover bg-center min-h-screen flex items-center justify-center" style="background-image: url('img2.jpg');"></body>

  <div class="bg-gray-300 p-6 rounded-lg shadow-md w-96">
    <h2 class="text-2xl font-bold mb-4 text-center">Login</h2>

    <?php if ($loginError): ?>
      <p class="text-red-500 text-sm mb-4 text-center"><?= $loginError ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <input name="username" class="w-full p-2 border rounded" type="text" placeholder="Username" required />
      <input name="password" class="w-full p-2 border rounded" type="password" placeholder="Password" required />
      <button type="submit" class="bg-blue-500 text-white w-full py-2 rounded hover:bg-blue-600 transition">Login</button>
    </form>

    <p class="text-sm mt-3 text-center">
      Don't have an account?
      <a class="text-blue-500 hover:underline" href="regi.php">Register</a>
    </p>
  </div>
</body>
</html>
