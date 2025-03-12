function sendOTP(){
    const email= document.getElementById('email').value;
    
    fetch('getOTP.php')
        .then(response => response.json())
        .then(data => {
            if (data.otp){
                let emailbody = `<h2>Your OTP is </h2>${data.otp}<p>Take it!!!!!!!.</p>`;
                Email.send({
                    SecureToken : "d6d5e352-11ba-400b-9f28-e794565846c3",
                    To : email,
                    From : "ALLAH-HAMBAK@gmail.com",
                    Subject : "FYP2025 OTP Verification",
                    Body : emailbody,
                }).then(
                    message => {
                        if (message === "OK"){
                            alert("OTP sent to your email " + email.value);
            
                            const otp_inp = document.getElementById('otp_inp');
                            const otp_btn = document.getElementById('otp-btn');
            
                            otp_btn.addEventListener('click', ()=> {
                                if(otp_inp.value == otp_val){
                                    alert("Email address verified");
                                }else{
                                    alert("Invalid OTP");
                                }
                            })
                        }
                    }
                );
            }
        }
    )

}