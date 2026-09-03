<?php
require_once '../Database.php';
$database = new Database();
$pdo = $database->getConnection();

$success = '';
$error = '';

// Handle volunteer registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_volunteer'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $skills = isset($_POST['skills']) ? implode(', ', $_POST['skills']) : '';
    $availability = isset($_POST['availability']) ? implode(', ', $_POST['availability']) : '';
    $preferred_community = $_POST['preferred_community'] ?? '';
    
    // Check if already registered
    $check = $pdo->prepare("SELECT id FROM volunteers WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->fetch()) {
        $error = "You are already registered as a volunteer!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO volunteers (user_id, full_name, email, phone, skills, availability, preferred_community) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$user_id, $full_name, $email, $phone, $skills, $availability, $preferred_community])) {
            $success = "Thank you for volunteering! We'll contact you soon.";
            
            // Create notification for admin
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (1, 'volunteer', 'New Volunteer Registration', '$full_name has registered as a volunteer')");
            $notifStmt->execute();
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}

// Get volunteer's tasks if logged in
$myTasks = [];
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    $taskStmt = $pdo->prepare("SELECT t.* FROM volunteer_tasks t JOIN volunteers v ON t.volunteer_id = v.id WHERE v.email = ? ORDER BY t.due_date ASC");
    $taskStmt->execute([$_SESSION['user_email']]);
    $myTasks = $taskStmt->fetchAll();
}

// Get upcoming events
$upcomingEvents = [
    ['title' => 'Borehole Maintenance Training', 'date' => '2026-06-15', 'location' => 'Limpopo', 'spots' => 15],
    ['title' => 'Community Water Quality Testing', 'date' => '2026-06-22', 'location' => 'Mpumalanga', 'spots' => 10],
    ['title' => 'Sanitation Facility Construction', 'date' => '2026-07-01', 'location' => 'North West', 'spots' => 20],
    ['title' => 'Water Conservation Workshop', 'date' => '2026-07-10', 'location' => 'Eastern Cape', 'spots' => 25]
];

// Get volunteer statistics
$volCount = $pdo->query("SELECT COUNT(*) as count FROM volunteers")->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer - CleanFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-slate-100 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
        <a href="../index.php" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 mb-6">← Back to Home</a>
        
        <?php if ($success): ?>
            <div class="bg-green-100 text-green-700 border border-green-200 rounded-lg p-4 mb-6"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 border border-red-200 rounded-lg p-4 mb-6"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Volunteer Registration Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-xl p-8">
                    <h2 class="text-2xl font-bold text-slate-800 mb-2">Join as a Volunteer</h2>
                    <p class="text-slate-600 mb-6">Make a difference in rural communities</p>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="register_volunteer" value="1">
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Full Name *</label>
                                <input type="text" name="full_name" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Email Address *</label>
                                <input type="email" name="email" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                            </div>
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Phone Number *</label>
                                <input type="tel" name="phone" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Preferred Community</label>
                                <select name="preferred_community" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
                                    <option value="">Select region</option>
                                    <option value="Limpopo">Limpopo</option>
                                    <option value="Mpumalanga">Mpumalanga</option>
                                    <option value="North West">North West</option>
                                    <option value="Eastern Cape">Eastern Cape</option>
                                    <option value="Any">Any - I'm flexible</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-3">Skills & Expertise</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="skills[]" value="Plumbing" class="rounded"> Plumbing
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="skills[]" value="Construction" class="rounded"> Construction
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="skills[]" value="Teaching" class="rounded"> Teaching
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="skills[]" value="Engineering" class="rounded"> Engineering
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="skills[]" value="Healthcare" class="rounded"> Healthcare
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="skills[]" value="Community Liaison" class="rounded"> Community Liaison
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-3">Availability</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="availability[]" value="Weekdays"> Weekdays
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="availability[]" value="Weekends"> Weekends
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="availability[]" value="Full Time"> Full Time
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="availability[]" value="Part Time"> Part Time
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition">🤝 Join as Volunteer</button>
                    </form>
                </div>
                
                <!-- My Tasks Section (if logged in and has tasks) -->
                <?php if (!empty($myTasks)): ?>
                <div class="bg-white rounded-2xl shadow-xl p-8 mt-8">
                    <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">✅ My Assigned Tasks</h3>
                    <div class="space-y-3">
                        <?php foreach ($myTasks as $task): ?>
                        <div class="p-4 border rounded-lg hover:shadow-md transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-semibold text-slate-800"><?php echo htmlspecialchars($task['task_name']); ?></h4>
                                    <p class="text-sm text-slate-600 mt-1"><?php echo htmlspecialchars($task['description']); ?></p>
                                    <?php if ($task['due_date']): ?>
                                    <p class="text-xs text-slate-400 mt-2">Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php 
                                    echo $task['status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                                        ($task['status'] == 'completed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'); ?>">
                                    <?php echo ucfirst($task['status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Stats Card -->
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl p-6 text-white">
                    <h3 class="font-semibold mb-3 text-lg">Make an Impact</h3>
                    <p class="text-sm text-blue-100 mb-4">Volunteers are the heart of our mission. Join us in bringing clean water to rural communities.</p>
                    <div class="text-center pt-2">
                        <div class="text-4xl font-bold"><?php echo $volCount; ?>+</div>
                        <div class="text-sm text-blue-100">Active Volunteers</div>
                    </div>
                </div>
                
                <!-- Upcoming Events -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">📅 Upcoming Events</h3>
                    <div class="space-y-4">
                        <?php foreach ($upcomingEvents as $event): ?>
                            <div class="p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                                <h4 class="font-medium text-slate-800"><?php echo $event['title']; ?></h4>
                                <div class="text-sm text-slate-600 mt-1">📅 <?php echo date('M d, Y', strtotime($event['date'])); ?></div>
                                <div class="text-sm text-slate-600">📍 <?php echo $event['location']; ?></div>
                                <div class="text-xs text-blue-600 mt-2">🎟️ <?php echo $event['spots']; ?> spots available</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Impact Numbers -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="font-semibold text-slate-800 mb-4">Our Impact</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-2 bg-green-50 rounded">
                            <span>💧 Communities Served</span>
                            <span class="font-bold text-green-600">50+</span>
                        </div>
                        <div class="flex justify-between items-center p-2 bg-blue-50 rounded">
                            <span>🚰 Water Projects</span>
                            <span class="font-bold text-blue-600">120+</span>
                        </div>
                        <div class="flex justify-between items-center p-2 bg-purple-50 rounded">
                            <span>👥 Lives Impacted</span>
                            <span class="font-bold text-purple-600">10,000+</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>