<?php
include 'config.php';
include 'header.php';

if (!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $feedback = trim($_POST['feedback'] ?? '');
    $feedback_date = date('Y-m-d H:i:s');

    if ($rating < 1 || $rating > 5){
        $error_message = "Please select a rating between 1 and 5 stars.";
    }else if(empty($feedback)){
        $error_message = "Please enter you feedback";
    }else{
        try{
            $stmt = $conn -> prepare("INSERT INTO feedback_rating (CustID, Rating, Feedback, FeedbackDate) VALUES (?, ?, ?, ?)");
            $stmt -> execute([$user_id, $rating, $feedback, $feedback_date]);

            $success_message = "Thank you for your feedback!!";
        }catch (PDOException $e) {
            $error_message = "Error submitting feedback. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="feedback.css">
</head>

<body class="feedback-body">
    <div class="feedback-container">
        <?php if ($success_message): ?>
            <div class="thank-you visible">
                <div class="thank-you-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Thank You!</h2>
                <p><?= htmlspecialchars($success_message) ?></p>
                <a href="index.php" class="feedback-submit-btn">Return to Home</a>
            </div>
        <?php else: ?>
            <div class="feedback-header">
                <h1>Share Your Feedback</h1>
                <p>We will view your feedback. Please let us know about your experience.</p>
            </div>

            <?php if ($error_message): ?>
                <div class="feedback-message error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <form class="feedback-form" method="post" action="feedback.php">
                <div class="feedback-form-group">
                    <label class="feedback-label">How would you rate your experience?</label>
                    <div class="feedback-rating-container">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" class="feedback-rating-input" <?= ($i == 0) ? 'checked' : '' ?>>
                            <label for="star<?= $i ?>" class="feedback-rating-star"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="feedback-form-group">
                    <label class="feedback-label" for="feedback">Your Feedback (Optional)</label>
                    <textarea id="feedback" name="feedback" class="feedback-textarea" placeholder="Tell us about your experience..."></textarea>
                </div>

                <button type="submit" class="feedback-submit-btn">Submit Feedback</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Enhance star rating interaction
        document.querySelectorAll('.feedback-rating-star').forEach(star => {
            star.addEventListener('click', (e) => {
                const radio = e.currentTarget.previousElementSibling;
                radio.checked = true;
                
                // Update all stars to reflect selection
                const stars = document.querySelectorAll('.feedback-rating-star');
                const rating = parseInt(radio.value);
                
                stars.forEach((s, index) => {
                    if (index + 1 <= rating) {
                        s.style.color = '#ff6600';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });

        document.querySelector('.feedback-rating-container').addEventListener('mouseleave', () => {
            const checkedRadio = document.querySelector('.feedback-rating-input:checked');
            if (checkedRadio){
                const rating = parseInt(checkedRadio.value);
                const stars = document,querySelectorAll('.feedback-rating-start');

                stars.forEach((s, index) => {
                    if (index < rating)
                        star.style.color = '#ff6600';
                    else
                        star.style.color = '#ddd';
                });
            }
        });

        document.querySelectorAll('.feedback-rating-input').forEach(input => {
            input.addEventListener('change', () => {
                const stars = document.querySelectorAll('.feedback-rating-star');
                stars.forEach(star => star.style.color = '#ddd');
                
                if (input.checked) {
                    const rating = parseInt(input.value);
                    for (let i = 1; i <= rating; i++) {
                        document.querySelector(`.feedback-rating-star[for="star${i}"]`).style.color = '#ff6600';
                    }
                }
            });
        });
    </script>
</body>
</html>

<?php
include 'footer.php';
?>