<?php
if (!isset($_SESSION['register_data'])) {
    header("Location: register.php?step=1");
    exit();
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $custAddress = filter_input(INPUT_POST, 'custAddress', FILTER_SANITIZE_STRING);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
    $postcode = filter_input(INPUT_POST, 'postcode', FILTER_SANITIZE_STRING);
    $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
    $isNotBot = isset($_POST['not-bot']) ? true : false;

    // Bot verification
    if (!$isNotBot) {
        $errors['not-bot'] = "Please confirm you are not a bot";
    }

    // Validate postcode format (Malaysian 5-digit)
    if (!preg_match("/^\d{5}$/", $postcode)) {
        $errors['postcode'] = "Please enter a valid 5-digit postcode";
    }

    if (empty($errors)) {
        // Complete registration
        $data = $_SESSION['register_data'];
        
        $sql = "INSERT INTO customer (CustName, CustEmail, CustPassword, CustPhoneNum, StreetAddress, City, Postcode, State) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $data['custName'],
            $data['custEmail'],
            $data['custPassword'],
            $data['custPhoneNum'],
            $custAddress,
            $city,
            $postcode,
            $state
        ]);

        if ($stmt->rowCount() > 0) {
            unset($_SESSION['register_data']);
            
            $_SESSION['registration_success'] = true;
            header("Location: register_success.php");
            exit();
        } else {
            $errors['general'] = "Error: Registration failed";
        }
    }
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const postcodeInput = document.querySelector('input[name="postcode"]');
    
    if (postcodeInput) {
        // Create error message element if it doesn't exist
        if (!postcodeInput.nextElementSibling || !postcodeInput.nextElementSibling.classList.contains('error-message')) {
            const errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            errorElement.style.display = 'none';
            errorElement.style.color = 'red';
            errorElement.style.marginTop = '5px';
            postcodeInput.parentNode.insertBefore(errorElement, postcodeInput.nextSibling);
        }
        
        postcodeInput.addEventListener('input', function() {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 5 digits
            if (this.value.length > 5) {
                this.value = this.value.slice(0, 5);
            }
            
            // Get the error element
            const errorElement = this.nextElementSibling;
            
            // Validate in real-time
            if (this.value.length > 0 && this.value.length < 5) {
                errorElement.textContent = "Postcode must be exactly 5 digits";
                errorElement.style.display = 'block';
                this.style.borderColor = 'red';
            } else if (this.value.length === 5) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                this.style.borderColor = '';
            } else if (this.value.length === 0) {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                this.style.borderColor = '';
            }
        });

        // Validate on blur (when leaving the field)
        postcodeInput.addEventListener('blur', function() {
            const errorElement = this.nextElementSibling;
            
            if (this.value.length > 0 && this.value.length < 5) {
                errorElement.textContent = "Postcode must be exactly 5 digits";
                errorElement.style.display = 'block';
                this.style.borderColor = 'red';
            }
        });
    }
});
</script>

<form method="POST" action="register.php?step=2" id="address-form">
    <label>Street Address:</label>
    <input type="text" name="custAddress" placeholder="4, Jalan Melodies 8, Taman Rainbow" value="<?php echo isset($_POST['custAddress']) ? htmlspecialchars($_POST['custAddress']) : ''; ?>" required>
    <?php if (isset($errors['custAddress'])): ?>
        <div class="error-message"><?php echo $errors['custAddress']; ?></div>
    <?php endif; ?>
    <br>

    <label>City:</label>
    <input type="text" name="city" placeholder="Johor Bahru" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" required><br>

    <label>Postcode:</label>
    <input type="text" name="postcode" placeholder="80100" value="<?php echo isset($_POST['postcode']) ? htmlspecialchars($_POST['postcode']) : ''; ?>" required maxlength="5">
    <?php if (isset($errors['postcode'])): ?>
        <div class="error-message"><?php echo $errors['postcode']; ?></div>
    <?php endif; ?>
    <br>

    <label>State:</label>
    <select name="state" class="state" required>
        <option value="">Select State</option>
        <?php
        $states = [
            "Johor", "Melaka", "Negeri Sembilan", "Kedah", "Kelantan", 
            "Pahang", "Penang", "Perak", "Perlis", "Sabah", 
            "Sarawak", "Selangor", "Terengganu", "Kuala Lumpur", "Putrajaya"
        ];
        foreach ($states as $stateOption) {
            $selected = (isset($_POST['state']) && $_POST['state'] == $stateOption) ? 'selected' : '';
            echo "<option value=\"$stateOption\" $selected>$stateOption</option>";
        }
        ?>
    </select><br>

    <div class="bot">
        <input type="checkbox" id="not-bot" name="not-bot" <?php if (isset($errors['not-bot'])) echo 'style="outline: 2px solid red;"'; ?> required>
        <label for="not-bot">I am not a bot</label>
        <?php if (isset($errors['not-bot'])): ?>
            <div class="error-message"><?php echo $errors['not-bot']; ?></div>
        <?php endif; ?>
    </div>

    <div class="form-navigation">
        <button type="submit" class="reg">Complete Registration</button>
        <button type="button" class="back-btn" onclick="window.location.href='register.php?step=1'">Back</button>
    </div>
</form>