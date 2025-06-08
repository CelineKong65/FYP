document.addEventListener('DOMContentLoaded', function() {
    // --- Toggle Password Visibility Logic ---
    const passwordInput = document.querySelector(".pass-field input");
    const eyeIcon = document.querySelector(".pass-field i");
    
    eyeIcon.addEventListener("click", ()=>{
        //Toggle password
        passwordInput.type = passwordInput.type === "password" ? "text" : "password";
    
        //Change eyeIcon 
        eyeIcon.className = `fa-solid fa-eye${passwordInput.type === "password" ? "" : "-slash"}`;
    
    });

    const loginForm = document.querySelector('form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // You can add client-side validation here if needed
            const email = this.querySelector('input[name="email"]').value.trim();
            const password = this.querySelector('input[name="password"]').value.trim();
            
            if (!email || !password) {
                e.preventDefault();
            }
        });
    }
});