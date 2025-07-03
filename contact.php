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
    
    // Server-side validation
    $errors = [];
    
    // Name validation (letters and spaces only)
    if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $errors['name'] = "Name can only contain letters and spaces";
    }
    
    // Email validation (must be gmail)
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@gmail\.com$/i", $email)) {
        $errors['email'] = "Please enter a valid Gmail address";
    }
    
    // Phone validation
    if (!empty($phone) && !preg_match('/^\d{3}-\d{3,4} \d{4}$/', $phone)) {
        $errors['phone'] = "Phone format: XXX-XXX XXXX or XXX-XXXX XXXX";
    }
    
    if (empty($errors)) {
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
            echo '<script>alert("Thanks for your message, we will read it carefully and reply as early as we could")</script>';
        } catch (PDOException $e) {
            // Error message
            echo '<script>alert("Your message failed to submit. Please try again. Thanks for your understanding.")</script>';
        }
    } else {
        // Store errors in session to display them
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
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
        'address' => '1, Persiaran Southkey 1, Southkey, 80150 Johor Bahru, Johor Darul Tazim',
        'link' => 'https://maps.app.goo.gl/tKUkBDz8KKBz249a6',
        'hours' => 'Mon-Sun: 10:00 AM - 10:00 PM',
        'phone' => '+60 3-9876 5432'
    ],
    [
        'name' => 'Bandaraya Melaka Aeon Waterfront Store',
        'address' => '2, Jalan Lagenda, Taman 1 Lagenda, 75400 Melaka',
        'link' => 'https://maps.app.goo.gl/ERbKqSWDiKcLHPp99',
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
                <div class="store-card">
                    <div class="store-map">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.4486768626625!2d103.7774187!3d1.5013337999999998!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31da6ce180efcfb5%3A0x4cfa3e126a713ab5!2sThe%20Mall%2C%20Mid%20Valley%20Southkey!5e0!3m2!1sen!2smy!4v1748962672287!5m2!1sen!2smy" 
                                width="1000" 
                                height="600" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                    <div class="store-info">
                        <h3>Johor Bahru Flagship Store</h3>
                        <p>
                            <i class="fas fa-map-marker-alt"></i> 
                            <a href="https://maps.app.goo.gl/tKUkBDz8KKBz249a6" target="_blank">
                                1, Persiaran Southkey 1, Southkey, 80150 Johor Bahru
                            </a>
                        </p>
                        <p class="store-hours">
                            <i class="far fa-clock"></i> Mon-Sun: 10:00 AM - 10:00 PM
                        </p>
                        <p>
                            <i class="fas fa-phone"></i> 
                            <a href="tel:+60398765432">+60 3-9876 5432</a>
                        </p>
                    </div>
                </div>

                <div class="store-card">
                    <div class="store-map">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d7973.675311319753!2d102.2463583!3d2.2148518000000004!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31d1f02e9b962b61%3A0xd4f66b8c4369e6fc!2sAEON%20MALL%20Bandaraya%20Melaka!5e0!3m2!1sen!2smy!4v1748961671207!5m2!1sen!2smy" 
                            width="100%" 
                            height="200" 
                            style="border:0;" 
                            allowfullscreen 
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                    <div class="store-info">
                        <h3>Bandaraya Melaka Aeon Waterfront Store</h3>
                        <p>
                            <i class="fas fa-map-marker-alt"></i> 
                            <a href="https://maps.app.goo.gl/ERbKqSWDiKcLHPp99" target="_blank">
                                2, Jalan Lagenda, Taman 1 Lagenda, 75400 Melaka
                            </a>
                        </p>
                        <p class="store-hours">
                            <i class="far fa-clock"></i> Mon-Sun: 10:00 AM - 9:00 PM
                        </p>
                        <p>
                            <i class="fas fa-phone"></i> 
                            <a href="tel:+6041234567">+60 4-123 4567</a>
                        </p>
                    </div>
                </div>
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
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($_SESSION['form_data']['name'] ?? '') ?>" required>
                        <div class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? '') ?>" required>
                        <div class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_SESSION['form_data']['phone'] ?? '') ?>">
                        <div class="error-message"></div>
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

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');

            // Real-time name validation
            nameInput.addEventListener('input', function() {
                // Remove any non-letter characters
                this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                validateName();
            });

            // Real-time email validation
            emailInput.addEventListener('input', validateEmail);

            // Real-time phone validation
            phoneInput.addEventListener('input', function() {
                validatePhone();
            });
                
            phoneInput.addEventListener('blur', function(){
                validatePhone();
            });

            // Form submission validation
            form.addEventListener('submit', function(e) {
                const isNameValid = validateName();
                const isEmailValid = validateEmail();
                const isPhoneValid = validatePhone();
                
                if (!isNameValid || !isEmailValid || !isPhoneValid) {
                    e.preventDefault();
                }
            });

            function validateName() {
                const isValid = /^[a-zA-Z\s]+$/.test(nameInput.value);
                const errorElement = nameInput.nextElementSibling;
                
                if (!isValid && nameInput.value) {
                    showError(nameInput, "Name can only contain letters and spaces");
                    return false;
                } else {
                    clearError(nameInput);
                    return true;
                }
            }

            function validateEmail() {
                const isValid = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i.test(emailInput.value);
                const errorElement = emailInput.nextElementSibling;
                
                if (!isValid && emailInput.value) {
                    showError(emailInput, "Please enter a valid Gmail address");
                    return false;
                } else {
                    clearError(emailInput);
                    return true;
                }
            }

            function validatePhone() {
                const value = phoneInput.value;
                const isValid = phoneInput.value === '' || /^\d{3}-\d{3,4} \d{4}$/.test(value);
                const errorElement = phoneInput.nextElementSibling;
                
                if (!isValid && phoneInput.value) {
                    showError(phoneInput, "Phone format: XXX-XXX XXXX or XXX-XXXX XXXX");
                    return false;
                } else {
                    clearError(phoneInput);
                    return true;
                }
            }

            function showError(input, message) {
                let errorElement = input.nextElementSibling;
                
                while (errorElement && !errorElement.classList.contains('error-message')) {
                    errorElement = errorElement.nextElementSibling;
                }
                
                if (!errorElement) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'error-message';
                    input.parentNode.insertBefore(errorElement, input.nextSibling);
                }
                
                errorElement.textContent = message;
                input.classList.add('error-field');
            }

            function clearError(input) {
                let errorElement = input.nextElementSibling;
                
                while (errorElement && !errorElement.classList.contains('error-message')) {
                    errorElement = errorElement.nextElementSibling;
                }
                
                if (errorElement) {
                    errorElement.textContent = '';
                }
                input.classList.remove('error-field');
            }
        });
    </script>
</body>
</html>