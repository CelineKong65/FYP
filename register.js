document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.querySelector(".pass-field input");
    const eyeIcon = document.querySelector(".pass-field i");
    const requirementList = document.querySelectorAll(".password-req li");
    const forms = document.querySelectorAll("form");
    const nameInput = document.getElementById('custName');
    const emailInput = document.getElementById('custEmail');
    const phoneInput = document.getElementById('custPhoneNum');

    const requirements = [
        {regex: /\S{8,}/, index: 0},
        {regex: /[A-Z]/, index: 1},
        {regex: /[a-z]/, index: 2},
        {regex: /\d/, index: 3},
        {regex: /[@$!%*#?&]/, index: 4},
        {regex: /^\S*$/, index: 5}
    ];

    // Password validation
    if (passwordInput) {
        passwordInput.addEventListener("keyup", (e) => {
            requirements.forEach(item => {
                const isValid = item.regex.test(e.target.value);
                const requirementItem = requirementList[item.index];
                
                requirementItem.firstElementChild.className = isValid ? 
                    "fa-solid fa-circle-check" : "fa-solid fa-circle";
                requirementItem.classList.toggle("valid", isValid);
            });
        });
    }

// Name validation functions
    function validateName(inputElement) {
        const value = inputElement.value.trim();
        const errorElement = document.getElementById('custName-error');
        
        if (!value) {
            errorElement.textContent = 'Name is required';
            errorElement.style.display = 'block';
            inputElement.classList.add('error');
            return false;
        }
        
        if (!/^[a-zA-Z\s]+$/.test(value)) {
            errorElement.textContent = 'Name can only contain letters and spaces';
            errorElement.style.display = 'block';
            inputElement.classList.add('error');
            return false;
        }
        
        errorElement.textContent = '';
        errorElement.style.display = 'none';
        inputElement.classList.remove('error');
        return true;
    }

    // Real-time check for username
    if (nameInput) {
        // Real-time validation on input
        nameInput.addEventListener('input', function() {
            const value = this.value;
            const errorElement = document.getElementById('custName-error');
            
            // First, prevent non-letter characters from being entered
            this.value = value.replace(/[^a-zA-Z\s]/g, '');
            
            // Then validate
            if (value !== this.value) { // If we modified the input
                errorElement.textContent = 'Name can only contain letters and spaces';
                errorElement.style.display = 'block';
                this.classList.add('error');
            } else if (this.value.trim() === '') {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                this.classList.remove('error');
            } else {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
                this.classList.remove('error');
            }
        });

        // Check availability on blur
        nameInput.addEventListener('blur', function() {
            if (this.value.trim() && !this.classList.contains('error')) {
                checkAvailability('username', this.value, 'custName-error');
            }
        });
    }

    if (emailInput) {
        emailInput.addEventListener('input', function() {
            const nameError = document.getElementById('custName-error');
            if (nameError && nameError.textContent.includes('already used with this email account')) {
                nameError.textContent = '';
                nameError.style.display = 'none';
                document.getElementById('custName').classList.remove('error');
            }
        });

        emailInput.addEventListener('blur', function() {
            // First validate email format
            const emailReq = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;
            if (!emailReq.test(this.value)) {
                document.getElementById('custEmail-error').textContent = "Please enter a valid gmail address";
                document.getElementById('custEmail-error').style.display = 'block';
                this.classList.add('error');
                return;
            }

            document.getElementById('custEmail-error').textContent = '';
            document.getElementById('custEmail-error').style.display = 'none';
            this.classList.remove('error');
            
            checkAvailability('email', this.value, 'custEmail-error');
            
            const nameError = document.getElementById('custName-error');
            if (nameError && nameError.textContent.includes('already used with this email account')) {
                const nameValue = document.getElementById('custName').value.trim();
                if (nameValue) {
                    checkAvailability('username', nameValue, 'custName-error');
                } else {
                    nameError.textContent = '';
                    nameError.style.display = 'none';
                    document.getElementById('custName').classList.remove('error');
                }
            }
        });
    }

    // Real-time check for phone number
    if (phoneInput) {
        phoneInput.addEventListener('blur', function() {
            validateContactNumber();
        });
    }

    // Toggle password visibility
    if (eyeIcon) {
        eyeIcon.addEventListener("click", togglePasswordVisibility);
    }

    // Form validation
    forms.forEach(form => {
        form.addEventListener("submit", function(e) {
            let isValid = true;

            if (form.id === "register-form") {
                // Check real-time validation
                const nameError = document.getElementById('custName-error');
                const emailError = document.getElementById('custEmail-error');
                const phoneError = document.getElementById('custPhoneNum-error');

                if (nameError && nameError.textContent !== '') isValid = false;
                if (emailError && emailError.textContent !== '') isValid = false;
                if (phoneError && phoneError.textContent !== '') isValid = false;

                // Check password requirements on submit
                if (passwordInput) {
                    requirements.forEach(item => {
                        if (!item.regex.test(passwordInput.value)) {
                            isValid = false;
                        }
                    });
                }
            } else if (form.id === "address-form") {
                // Validate step 2 form (as before)
                const botCheck = document.getElementById("not-bot");
                if (!botCheck.checked) {
                    showError(botCheck, "Please confirm you are not a robot");
                    isValid = false;
                }
            }

            if (!isValid) e.preventDefault();
        });
    });

    function checkAvailability(type, value, errorId) {
        if (!value.trim()) {
            document.getElementById(errorId).textContent = '';
            return;
        }

        let isValidFormat = true;
        let errorMessage = '';

        if (type === 'email') {
            const emailReq = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;
            if (!emailReq.test(value)) {
                isValidFormat = false;
                errorMessage = "Please enter a valid gmail address";
            }
        } else if (type === 'phone') {
            const phoneReq = /^\d{3}-\d{3,4} \d{4}$/;
            if (!phoneReq.test(value)) {
                isValidFormat = false;
                errorMessage = "Format: XXX-XXX XXXX or XXX-XXXX XXXX";
            }
        }

        if (!isValidFormat) {
            document.getElementById(errorId).textContent = errorMessage;
            document.getElementById(errorId).style.display = 'block';
            document.getElementById(errorId.replace('-error', '')).classList.add('error');
            return;
        } else {
            document.getElementById(errorId).textContent = '';
            document.getElementById(errorId).style.display = 'none';
            document.getElementById(errorId.replace('-error', '')).classList.remove('error');
        }

        const formData = new FormData();
        formData.append('check_availability', 'true');
        formData.append('type', type);
        formData.append('value', value);

        if (type === 'username') {
            const emailValue = document.getElementById('custEmail').value;
            formData.append('email', emailValue);
        }

        fetch(window.location.href, {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                document.getElementById(errorId).textContent = data.message || `${type.charAt(0).toUpperCase() + type.slice(1)} already exists`;
                document.getElementById(errorId).style.display = 'block';
                document.getElementById(errorId.replace('-error', '')).classList.add('error');
            } else {
                document.getElementById(errorId).textContent = '';
                document.getElementById(errorId).style.display = 'none';
                document.getElementById(errorId.replace('-error', '')).classList.remove('error');
            }
        })
        .catch(error => {
            console.error('Error checking availability:', error);
        });
    }

    function togglePasswordVisibility() {
        passwordInput.type = passwordInput.type === "password" ? "text" : "password";
        eyeIcon.className = `fa-solid fa-eye${passwordInput.type === "password" ? "" : "-slash"}`;
    }

    function showError(inputElement, message) {
        let errorElement = inputElement.nextElementSibling;
        while (errorElement && !errorElement.classList.contains('error-message')) {
            errorElement = errorElement.nextElementSibling;
        }

        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'error-message';
            inputElement.parentNode.insertBefore(errorElement, inputElement.nextSibling);
        }

        errorElement.textContent = message;
        errorElement.style.display = 'block';
        inputElement.classList.add('error');
    }

    function hideError(inputElement) {
        let errorElement = inputElement.nextElementSibling;
        while (errorElement && !errorElement.classList.contains('error-message')) {
            errorElement = errorElement.nextElementSibling;
        }

        if (errorElement) {
            errorElement.style.display = 'none';
            inputElement.classList.remove('error');
        }
    }

    function validateContactNumber() {
        const value = phoneInput.value.trim();
        const errorElement = document.getElementById('custPhoneNum-error');
        
        if (!value) {
            errorElement.textContent = 'Contact number is required';
            errorElement.style.display = 'block';
            phoneInput.classList.add('error');
            return false;
        }
        
        // Validate either XXX-XXX XXXX or XXX-XXXX XXXX format
        if (!/^\d{3}-\d{3,4} \d{4}$/.test(value)) {
            errorElement.textContent = 'Format: XXX-XXX XXXX or XXX-XXXX XXXX';
            errorElement.style.display = 'block';
            phoneInput.classList.add('error');
            return false;
        }
        
        // If valid, clear error and check availability
        errorElement.textContent = '';
        errorElement.style.display = 'none';
        phoneInput.classList.remove('error');
        checkAvailability('phone', value, 'custPhoneNum-error');
        return true;
    }
});
