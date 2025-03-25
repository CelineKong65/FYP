<?php
$team_members = [
    [
        'name' => 'Kong Lee Ching',
        'role' => 'Team Leader',
        'description' => 'With a keen eye for detail and a passion for innovation, Kong ensures our platform delivers the best user experience and top-notch products.',
        'image' => 'team1.jpg'
    ],
    [
        'name' => 'Pan Zhi Xin',
        'role' => 'Tech Specialist',
        'description' => 'Our tech-savvy member who ensures our e-commerce platform runs smoothly, making your shopping experience seamless and hassle-free.',
        'image' => 'team2.jpg'
    ],
    [
        'name' => 'Lawrence Miguel Tan Qi Yuan',
        'role' => 'Creative Director',
        'description' => 'The creative mind behind our branding and marketing efforts, Lawrence ensures our message resonates with watersport enthusiasts everywhere. "BUT that not true, is fake haha got ya"',
        'image' => 'team3.jpg'
    ]
];

$sports = [
    'Snorkeling' => 'image/snorkeling.jpg',
    'Paddleboarding' => 'image/paddleboarding.jpg',
    'Kayaking' => 'image/kayaking.jpg',
    'Surfing' => 'image/surfing.jpg',
    'Scuba Diving' => 'image/google2.jpg'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Watersport Equipment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="about_us.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <h1 class="logo">Watersport Equipment</h1>
            <p class="tagline">Your ultimate destination for high-quality watersport gear and accessories!</p>
        </div>
    </header>
    
    <div class="container">
        <section class="section">
            <div class="mission scroll-animate">
                <h2 class="section-title">Our Story</h2>
                <p class="formation-date">Our team is forming in 12 December 2024</p>
                
                <p class="mission-text">We are passionate about making watersports accessible, enjoyable, and safe for everyone, whether you're a seasoned pro or just dipping your toes into the world of aquatic adventures.</p>
                
                <p class="mission-text">At Watersport Equipment, we believe that the right gear can make all the difference in your watersport journey. That's why we carefully curate our collection to include only the most reliable, durable, and performance-driven products.</p>
                
                <p class="mission-text">Our mission is simple: to inspire and empower people to embrace the joy of watersports. Whether you're riding the waves, exploring coral reefs, or simply enjoying a day by the lake, we're here to equip you for every splash, dive, and adventure.</p>
            </div>
            
            <h2 class="section-title scroll-animate">Our Sports</h2>
            <div class="sports-grid">
                <?php foreach ($sports as $sport => $image): ?>
                <div class="sport-card scroll-animate">
                    <img src="<?php echo $image; ?>" alt="<?php echo $sport; ?>">
                    <div class="sport-name"><?php echo $sport; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <section class="section" style="background-color: var(--light); padding: 80px 20px; border-radius: 12px;">
            <div class="container">
                <h2 class="section-title scroll-animate">Meet Our Team</h2>
                <p class="scroll-animate" style="text-align: center; max-width: 800px; margin: 0 auto 40px;">We are a small but dedicated group of three individuals who share a common love for the water and a vision to revolutionize the way people shop for watersport equipment.</p>
                
                <div class="team">
                    <?php foreach ($team_members as $member): ?>
                    <div class="team-member scroll-animate">
                        <div class="team-img">
                            <img src="<?php echo $member['image']; ?>" alt="<?php echo $member['name']; ?>">
                        </div>
                        <div class="team-info">
                            <h3 class="team-name"><?php echo $member['name']; ?></h3>
                            <span class="team-role"><?php echo $member['role']; ?></span>
                            <p><?php echo $member['description']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <div class="insurance-note scroll-animate">
            Hope you get your insurance. If not, AIA is watching you
        </div>
    </div>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-logo">Watersport Equipment</div>
            <p class="footer-tagline">Thank you for choosing Watersport Equipment—where your next aquatic adventure begins!</p>
            <p class="footer-copyright">&copy; <?php echo date('Y'); ?> Watersport Equipment A.K.A FYP PROJECT. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const animateElements = document.querySelectorAll('.scroll-animate');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, {
                threshold: 0.1
            });
            
            animateElements.forEach(element => {
                observer.observe(element);
            });
        });
    </script>
</body>
</html>