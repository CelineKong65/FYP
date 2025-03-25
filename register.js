const passwordInput = document.querySelector(".pass-field input");
const eyeIcon = document.querySelector(".pass-field i");
const requirementList = document.querySelectorAll(".password-req li");

const requirements = [
    {regex: /\S{8,}/, index: 0},       // Minimum 8 non-whitespace characters
    {regex: /[A-Z]/, index: 1},        // At least one uppercase
    {regex: /[a-z]/, index: 2},        // At least one lowercase
    {regex: /\d/, index: 3},           // At least one digit
    {regex: /[@$!%*#?&]/, index: 4},   // At least one special symbol
    {regex: /^\S*$/, index: 5}         // No space
]

passwordInput.addEventListener("keyup", (e)=> {
    requirements.forEach(item => {
        //Check is the password match requirements
        const isValid = item.regex.test(e.target.value);
        const requirementItem = requirementList[item.index];

        if(isValid){
            requirementItem.firstElementChild.className = "fa-solid fa-circle-check";
            requirementItem.classList.add("valid");
        }else{
            requirementItem.firstElementChild.className = "fa-solid fa-circle";
            requirementItem.classList.remove("valid");
        }
    });
});

eyeIcon.addEventListener("click", ()=>{
    //Toggle password
    passwordInput.type = passwordInput.type === "password" ? "text" : "password";

    //Change eyeIcon 
    eyeIcon.className = `fa-solid fa-eye${passwordInput.type === "password" ? "" : "-slash"}`;

});