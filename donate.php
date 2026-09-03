<?php
require_once '../Database.php';
$database = new Database();
$pdo = $database->getConnection();

$success = '';
$error = '';

// Get donation statistics
$stmt = $pdo->query("SELECT SUM(amount) as total, COUNT(*) as count FROM donations WHERE status = 'completed'");
$stats = $stmt->fetch();
$total_raised = $stats['total'] ?? 0;
$donation_count = $stats['count'] ?? 0;

// Get recent donors
$recentDonors = $pdo->query("SELECT donor_name, amount, created_at FROM donations WHERE anonymous = 0 AND status = 'completed' ORDER BY created_at DESC LIMIT 5")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donor_name = $_POST['donor_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 'card';
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;
    $transaction_id = 'TXN-' . time() . rand(100, 999);
    
    $stmt = $pdo->prepare("INSERT INTO donations (donor_name, email, amount, payment_method, anonymous, transaction_id, status) VALUES (?, ?, ?, ?, ?, ?, 'completed')");
    
    if ($stmt->execute([$donor_name, $email, $amount, $payment_method, $anonymous, $transaction_id])) {
        $success = "Thank you for your donation of R" . number_format($amount, 2) . "!";
        
        // Create notification for admin
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (1, 'donation', 'New Donation Received', 'R$amount donation from $donor_name')");
        $notifStmt->execute();
    } else {
        $error = "Payment processing failed. Please try again.";
    }
}

$goal_amount = 500000;
$progress_percent = min(100, ($total_raised / $goal_amount) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate - CleanFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-slate-100 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
        <a href="../index.php" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 mb-6">← Back to Home</a>
        
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Main Donation Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h2 class="text-2xl font-bold text-slate-800 mb-2">Support Rural Water & Sanitation Projects</h2>
                    <p class="text-slate-600 mb-6">Your donation directly helps communities get access to clean water</p>
                    
                    <?php if ($success): ?>
                        <div class="bg-green-100 text-green-700 border border-green-200 rounded-lg p-4 mb-6"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 text-red-700 border border-red-200 rounded-lg p-4 mb-6"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-3">Select Donation Amount</label>
                            <div class="grid grid-cols-3 gap-3 mb-4">
                                <button type="button" onclick="setAmount(50)" class="amount-btn py-3 rounded-lg border-2 border-slate-200 hover:border-blue-600">R50</button>
                                <button type="button" onclick="setAmount(100)" class="amount-btn py-3 rounded-lg border-2 border-slate-200 hover:border-blue-600">R100</button>
                                <button type="button" onclick="setAmount(250)" class="amount-btn py-3 rounded-lg border-2 border-slate-200 hover:border-blue-600">R250</button>
                                <button type="button" onclick="setAmount(500)" class="amount-btn py-3 rounded-lg border-2 border-slate-200 hover:border-blue-600">R500</button>
                                <button type="button" onclick="setAmount(1000)" class="amount-btn py-3 rounded-lg border-2 border-slate-200 hover:border-blue-600">R1,000</button>
                                <button type="button" onclick="setAmount(2500)" class="amount-btn py-3 rounded-lg border-2 border-slate-200 hover:border-blue-600">R2,500</button>
                            </div>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 font-medium">R</span>
                                <input type="number" name="amount" id="donationAmount" required placeholder="Custom amount" class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                            </div>
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Donor Name *</label>
                                <input type="text" name="donor_name" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Email Address *</label>
                                <input type="email" name="email" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-3">Payment Method</label>
                            <div class="grid grid-cols-3 gap-4">
                                <label class="flex flex-col items-center p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-blue-600">
                                    <input type="radio" name="payment_method" value="card" class="sr-only" checked>
                                    <span class="text-2xl">💳</span>
                                    <span class="text-sm font-medium">Card</span>
                                </label>
                                <label class="flex flex-col items-center p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-blue-600">
                                    <input type="radio" name="payment_method" value="eft" class="sr-only">
                                    <span class="text-2xl">🏦</span>
                                    <span class="text-sm font-medium">EFT</span>
                                </label>
                                <label class="flex flex-col items-center p-4 border-2 border-slate-200 rounded-lg cursor-pointer hover:border-blue-600">
                                    <input type="radio" name="payment_method" value="mobile_payment" class="sr-only">
                                    <span class="text-2xl">📱</span>
                                    <span class="text-sm font-medium">Mobile Payment</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="anonymous" id="anonymous">
                            <label for="anonymous" class="text-sm text-slate-600">Make this donation anonymous</label>
                        </div>
                        
                        <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">❤️ Donate Securely</button>
                        
                        <p class="text-center text-sm text-slate-500">Your payment is secure and encrypted. You will receive a tax receipt via email.</p>
                    </form>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="font-semibold text-slate-800 mb-4">Campaign Progress</h3>
                    <div class="mb-3">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-slate-600">R<?php echo number_format($total_raised); ?> raised</span>
                            <span class="font-semibold">R<?php echo number_format($goal_amount); ?></span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full" style="width: <?php echo $progress_percent; ?>%"></div>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600">Water infrastructure upgrades in 5 rural communities</p>
                </div>
                
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="font-semibold text-slate-800 mb-4">Your Impact</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="text-2xl">💧</div>
                            <div><div class="font-semibold">R250</div><div class="text-sm text-slate-600">Repairs one borehole</div></div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="text-2xl">🚽</div>
                            <div><div class="font-semibold">R500</div><div class="text-sm text-slate-600">Builds a new toilet facility</div></div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="text-2xl">💦</div>
                            <div><div class="font-semibold">R1000</div><div class="text-sm text-slate-600">Installs a water tank</div></div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="font-semibold text-slate-800 mb-4">Recent Donors</h3>
                    <div class="space-y-3">
                        <?php foreach ($recentDonors as $donor): ?>
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-medium"><?php echo htmlspecialchars($donor['donor_name']); ?></div>
                                    <div class="text-xs text-slate-500"><?php echo date('M d, H:i', strtotime($donor['created_at'])); ?></div>
                                </div>
                                <div class="font-semibold text-blue-600">R<?php echo number_format($donor['amount']); ?></div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($recentDonors)): ?>
                            <p class="text-slate-500 text-sm">No donations yet. Be the first!</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function setAmount(amount) {
            document.getElementById('donationAmount').value = amount;
            document.querySelectorAll('.amount-btn').forEach(btn => {
                btn.classList.remove('border-blue-600', 'bg-blue-50');
                if (btn.innerText.includes(amount)) {
                    btn.classList.add('border-blue-600', 'bg-blue-50');
                }
            });
        }
    </script>
</body>
</html>