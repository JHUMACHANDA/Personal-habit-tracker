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

$register_error = "";

// --- Handle Registration Form Submission ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $raw_password = $_POST["password"];

    if ($username !== "" && $raw_password !== "") {
        // Password validation: letter, digit, special char, at least 6 characters
        if (
            preg_match("/[A-Za-z]/", $raw_password) &&
            preg_match("/\d/", $raw_password) &&
            preg_match("/[\W_]/", $raw_password) &&
            strlen($raw_password) >= 6
        ) {
            $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $register_error = "⚠️ Username already taken.";
            }
        } else {
            $register_error = "Password must include at least 1 letter, 1 number, 1 special character, and be at least 6 characters long.";
        }
    } else {
        $register_error = "Please fill in all fields.";
    }
}
?>

<!-- ✅ Registration Form UI -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-cover bg-center min-h-screen flex items-center justify-center" style="background-image: url('img3.jpg');"></body>

  <div class="bg-gray-400 p-6 rounded-lg shadow-md w-96">
    <h2 class="text-2xl font-bold mb-4">Register</h2>

    <?php if ($register_error): ?>
      <p class="text-red-500 text-sm mb-3"><?= $register_error ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <input name="username" class="w-full p-2 border rounded" type="text" placeholder="Username" required />
      <input name="password" class="w-full p-2 border rounded" type="password" placeholder="Password" required />
      <button type="submit" class="bg-green-500 text-white w-full py-2 rounded hover:bg-green-600">Register</button>
    </form>

    <p class="text-sm mt-3 text-center">
      Already have an account?
      <a class="text-blue-500 underline" href="login.php">Login</a>
    </p>
  </div>
</body>
</html>
