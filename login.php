<?php
require_once '../Database.php';
$database = new Database();
$pdo = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        header("Location: ../index.php");
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CleanFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-slate-100 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-6">
            <img src="../images/logo.png" alt="CleanFlow" class="h-16 mx-auto mb-3" onerror="this.src='https://via.placeholder.com/64?text=CF'">
            <h2 class="text-2xl font-bold text-slate-800">Welcome Back</h2>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 border border-red-200 rounded-lg p-4 mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Email Address</label>
                <input type="email" name="email" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                <input type="password" name="password" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center"><input type="checkbox" class="mr-2"> Remember me</label>
                <a href="#" class="text-sm text-blue-600 hover:underline">Forgot Password?</a>
            </div>
            <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Sign In</button>
        </form>
        <p class="text-center mt-6 text-slate-600">Don't have an account? <a href="register.php" class="text-blue-600 font-semibold">Create Account</a></p>
        <p class="text-center text-xs text-slate-400 mt-4">Demo: admin@cleanflow.org / admin123</p>
    </div>
</body>
</html>