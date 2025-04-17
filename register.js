// register.js
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.querySelector(".pass-field input");
    const eyeIcon = document.querySelector(".pass-field i");
    const requirementList = document.querySelectorAll(".password-req li");
    const emailInput = document.querySelector("input[name='custEmail']");
    const phoneInput = document.querySelector("input[name='custPhoneNum']");
    const forms = document.querySelectorAll("form");

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

    // Email validation
    if (emailInput) {
        emailInput.addEventListener("blur", validateEmail);
    }

    // Phone validation
    if (phoneInput) {
        phoneInput.addEventListener("blur", validatePhone);
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
                // Validate step 1 form
                if (emailInput && !validateEmail()) isValid = false;
                if (phoneInput && !validatePhone()) isValid = false;
                
                // Check password requirements
                if (passwordInput) {
                    requirements.forEach(item => {
                        if (!item.regex.test(passwordInput.value)) {
                            isValid = false;
                        }
                    });
                    
                    if (!isValid) {
                        showError(passwordInput, "Password does not meet all requirements");
                    }
                }
            } 
            else if (form.id === "address-form") {
                // Validate step 2 form
                const botCheck = document.getElementById("not-bot");
                if (!botCheck.checked) {
                    showError(botCheck, "Please confirm you are not a robot");
                    isValid = false;
                }
            }
            
            if (!isValid) e.preventDefault();
        });
    });

    function validateEmail() {
        const email = emailInput.value;
        const emailReq = /^[a-zA-Z0-9._%+-]+@gmail\.com$/i;
        
        if (email && !emailReq.test(email)) {
            showError(emailInput, "Please enter a valid gmail address");
            return false;
        } else {
            hideError(emailInput);
            return true;
        }
    }

    function validatePhone() {
        const phone = phoneInput.value;
        const phoneReq = /^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/;
        
        if (phone && !phoneReq.test(phone)) {
            showError(phoneInput, "Please enter a "-" in phone number");
            return false;
        } else {
            hideError(phoneInput);
            return true;
        }
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
});