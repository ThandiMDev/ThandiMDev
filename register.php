<?php
require_once '../Database.php';
$database = new Database();
$pdo = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'community_member';
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $error = "Email already registered!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$full_name, $email, $phone, $hashed_password, $role])) {
            $success = "Registration successful! Please login.";
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CleanFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-slate-100 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-xl p-8">
        <div class="text-center mb-6">
            <img src="../images/logo.png" alt="CleanFlow" class="h-16 mx-auto mb-3" onerror="this.src='https://via.placeholder.com/64?text=CF'">
            <h2 class="text-2xl font-bold text-slate-800">Create Your Account</h2>
            <p class="text-slate-600">Join CleanFlow and make a difference</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 border border-red-200 rounded-lg p-4 mb-4"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 border border-green-200 rounded-lg p-4 mb-4">
                <?php echo $success; ?> <a href="login.php" class="font-semibold underline">Login here</a>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Full Name *</label>
                <input type="text" name="full_name" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
            </div>
            
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email Address *</label>
                    <input type="email" name="email" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Phone Number</label>
                    <input type="tel" name="phone" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>
            </div>
            
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Password *</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Confirm Password *</label>
                    <input type="password" name="confirm_password" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">I want to join as a *</label>
                <select name="role" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                    <option value="community_member">Community Member</option>
                    <option value="donor">Donor</option>
                    <option value="volunteer">Volunteer</option>
                    <option value="field_worker">Field Worker</option>
                </select>
            </div>
            
            <div class="flex items-start gap-3">
                <input type="checkbox" id="terms" required class="mt-1">
                <label for="terms" class="text-sm text-slate-600">I agree to the Terms of Service and Privacy Policy</label>
            </div>
            
            <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Create Account</button>
        </form>
        
        <p class="text-center mt-6 text-slate-600">Already have an account? <a href="login.php" class="text-blue-600 font-semibold">Sign In</a></p>
    </div>
</body>
</html>