<?php 
include 'header.php';
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $submission_date = date('Y-m-d H:i:s');
    
    try {
        $stmt = $conn->prepare("INSERT INTO contact_record 
                              (CustName, CustEmail, CustPhoneNum, Subject, Message, Submission_date) 
                              VALUES (:name, :email, :phone, :subject, :message, :submission_date)");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':submission_date', $submission_date);
        
        $stmt->execute();
        
        // Success message
        echo '<script>alert("Thanks for your message, we will read it carefully and then delete it somehow")</script>';
    } catch (PDOException $e) {
        // Error message
        echo '<script>alert("Your message is snatch by web spider, go find it and submit again your message. Thank you")</script>';
    }
}

// Contact information
$contact_info = [
    'phone' => '+60 3-1234 5678',
    'email' => 'watersport@gmail.com',
    'address' => 'Jalan Ayer Keroh Lama, 75450 Bukit Beruang, Melaka'
];

// Store locations
$stores = [
    [
        'name' => 'Johor Bahru Flagship Store',
        'address' => '1, Persiaran Southkey 1<br>Southkey, 80150 Johor Bahru<br>Johor Darul Tazim',
        'hours' => 'Mon-Sun: 10:00 AM - 10:00 PM',
        'phone' => '+60 3-9876 5432'
    ],
    [
        'name' => 'Bandaraya Melaka Aeon Waterfront Store',
        'address' => '2, Jalan Lagenda<br>Taman 1 Lagenda<br>75400 Melaka',
        'hours' => 'Mon-Sun: 10:00 AM - 9:00 PM',
        'phone' => '+60 4-123 4567'
    ]
];

// FAQ categories
$faqs = [
    'Order & Delivery' => [
        'How long does delivery take?' => 'Standard delivery takes 3-5 working days. Express delivery available for selected areas (1-2 working days).',
        'Do you offer international shipping?' => 'Currently we only ship within Malaysia. We plan to expand to Southeast Asia in 2025.'
    ],
    'Product & Warranty' => [
        'Do products come with warranty?' => 'Yes, most products come with 1-year manufacturer warranty. Watersport electronics have 2-year warranty.'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="contact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="top">
        <div class="container">
            <h1>Contact KPL Watersport</h1>
            <p>We're here to help with any questions about our products or services</p>
        </div>
    </div>

    <section class="section">
        <div class="container">
            <h2 class="section-title">How Can We Help You?</h2>
            <div class="contact-methods">
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone-alt"></i>
                    </div>
                    <h3>Call Us</h3>
                    <p>Speak with our customer service team</p>
                    <a href="tel:<?= htmlspecialchars($contact_info['phone']) ?>"><?= htmlspecialchars($contact_info['phone']) ?></a>
                    <p>Mon-Sun, 9:00 AM - 6:00 PM</p>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email Us</h3>
                    <p>Send us your questions and feedback</p>
                    <a href="mailto:<?= htmlspecialchars($contact_info['email']) ?>"><?= htmlspecialchars($contact_info['email']) ?></a>
                    <p>Response within 24 hours</p>
                </div>
                
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Visit Us</h3>
                    <p>Our headquarters location</p>
                    <p><?= htmlspecialchars($contact_info['address']) ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="section stores">
        <div class="container">
            <h2 class="section-title">Our Store Locations</h2>
            <div class="store-cards">
                <?php foreach ($stores as $store): ?>
                <div class="store-card">
                    <div class="store-img" style="background-image: url('store-<?= strtolower(str_replace(' ', '-', explode(' ', $store['name'])[0])) ?>.jpg')"></div>
                    <div class="store-info">
                        <h3><?= htmlspecialchars($store['name']) ?></h3>
                        <p><?= $store['address'] ?></p>
                        <p class="store-hours"><?= htmlspecialchars($store['hours']) ?></p>
                        <p>Tel: <a href="tel:<?= str_replace(' ', '', $store['phone']) ?>"><?= htmlspecialchars($store['phone']) ?></a></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section contact-form">
        <div class="container">
            <h2 class="section-title">Do you have any question? Please let us know</h2>
            <div class="form-container">
                <form action="contact.php" method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="Product Inquiry">Product Inquiry</option>
                            <option value="Order Status">Order Status</option>
                            <option value="Returns & Refunds">Returns & Refunds</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" required></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="faq-container">
                <?php foreach ($faqs as $category => $questions): ?>
                <div class="faq-category">
                    <h3><?= htmlspecialchars($category) ?></h3>
                    <?php foreach ($questions as $question => $answer): ?>
                    <div class="faq-item">
                        <div class="faq-question"><?= htmlspecialchars($question) ?></div>
                        <div class="faq-answer"><?= htmlspecialchars($answer) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

<?php
    include 'footer.php';
?>
    <script>
        // FAQ toggle functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                question.classList.toggle('active');
                const answer = question.nextElementSibling;
                if (question.classList.contains('active')) {
                    answer.style.display = 'block';
                } else {
                    answer.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>