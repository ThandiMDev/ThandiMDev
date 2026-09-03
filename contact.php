<?php
require_once '../Database.php';
$database = new Database();
$pdo = $database->getConnection();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    $stmt = $pdo->prepare("INSERT INTO contact_messages (full_name, email, subject, message) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$full_name, $email, $subject, $message])) {
        $success = "Thank you for contacting us! We'll get back to you soon.";
    } else {
        $error = "Failed to send message. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - CleanFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-slate-100 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
        <a href="../index.php" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 mb-6">← Back to Home</a>
        
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Contact Form -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Get in Touch</h2>
                <p class="text-slate-600 mb-6">Have questions? We're here to help!</p>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 text-green-700 border border-green-200 rounded-lg p-4 mb-6"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 text-red-700 border border-red-200 rounded-lg p-4 mb-6"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Full Name *</label>
                        <input type="text" name="full_name" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Email Address *</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Subject</label>
                        <input type="text" name="subject" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Message *</label>
                        <textarea name="message" rows="6" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">Send Message</button>
                </form>
            </div>
            
            <!-- Contact Information -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h3 class="text-xl font-bold text-slate-800 mb-6">Contact Information</h3>
                    
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-2xl">📧</div>
                            <div>
                                <h4 class="font-medium text-slate-800 mb-1">Email</h4>
                                <p class="text-slate-600">info@cleanflow.org</p>
                                <p class="text-slate-600">support@cleanflow.org</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-2xl">📞</div>
                            <div>
                                <h4 class="font-medium text-slate-800 mb-1">Phone</h4>
                                <p class="text-slate-600">+27 12 345 6789</p>
                                <p class="text-slate-600">+27 12 345 6790</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-2xl">📍</div>
                            <div>
                                <h4 class="font-medium text-slate-800 mb-1">Office Address</h4>
                                <p class="text-slate-600">
                                    123 Water Street<br>
                                    Pretoria, 0001<br>
                                    South Africa
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h3 class="font-semibold text-slate-800 mb-4">Office Hours</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-slate-600">Monday - Friday</span>
                            <span class="font-medium">8:00 AM - 5:00 PM</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Saturday</span>
                            <span class="font-medium">9:00 AM - 1:00 PM</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-600">Sunday</span>
                            <span class="font-medium">Closed</span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white">
                    <h3 class="font-semibold mb-3">🚨 Emergency Hotline</h3>
                    <p class="text-blue-100 text-sm mb-4">For urgent infrastructure issues affecting community water supply:</p>
                    <div class="text-2xl font-bold">0800 CLEAN FLOW</div>
                    <div class="text-blue-100 text-sm mt-2">(Available 24/7)</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>