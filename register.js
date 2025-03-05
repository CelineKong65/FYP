const passwordInput = document.getElementById('custPassword');
const togglePassword = document.getElementById('togglePassword');
const requirements = {
    uppercase : document.getElementById('req-uppercase'),
    lowercase : document.getElementById('req-lowercase'),
    space : document.getElementById('req-space'),
    length : document.getElementById('req-length'),
};

//Function for password requirements
const updateReq = (password) => {
    //Uppercase
    if (/[A-Z]/.test(password)){
        requirements.uppercase.classList.remove('red');
        requirements.uppercase.classList.add('green');
    } else {
        requirements.uppercase.classList.remove('green');
        requirements.uppercase.classList.add('red');
    }

    //Lowercase
    if (/[a-z]/.test(password)){
        requirements.uppercase.classList.remove('red');
        requirements.uppercase.classList.add('green');
    } else {
        requirements.uppercase.classList.remove('green');
        requirements.uppercase.classList.add('red');
    }

    //Space
    if (!/\s/.test(password)){
        requirements.uppercase.classList.remove('red');
        requirements.uppercase.classList.add('green');
    } else {
        requirements.uppercase.classList.remove('green');
        requirements.uppercase.classList.add('red');
    }

    if (password.length >= 8){
        requirements.uppercase.classList.remove('red');
        requirements.uppercase.classList.add('green');
    } else {
        requirements.uppercase.classList.remove('green');
        requirements.uppercase.classList.add('red');
    }
};

passwordInput.addEventListener('input', () => {
    const password = passwordInput.value;

    if(password.length === 0){
        //Reset all circle to grey
        Object.values(requirements).forEach(req => {
            req.classList.remove('red','green');
        });
    }else{
        updateReq(password);
    }
});

//View password
togglePassword.addEventListener('click', () => {
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);

    const eyeIcon = togglePassword.querySelector('i');
    if (type === 'password'){
        togglePassword.classList.remove('bx-show-alt'); 
        togglePassword.classList.add('bxs-hide');
    } else {
        togglePassword.classList.remove('bxs-hide');
        togglePassword.classList.add('bx-show-alt');
    }
});