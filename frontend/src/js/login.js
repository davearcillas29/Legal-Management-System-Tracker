// This part gets the login form elements
document.addEventListener("DOMContentLoaded", function () {
    const loginForm = document.getElementById("login-form");
    const emailInput = document.getElementById("login-email");
    const passwordInput = document.getElementById("login-password");

    // This part of the code basically toggles the password whether the user wants to see the password by making it visible or hidden
    const togglePwBtn = document.getElementById("toggle-pw");
    const eyeIcon = document.getElementById("eye-icon");

    const eyeOpenPath = `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>`;
    const eyeClosedPath = `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>`;

    if (togglePwBtn && passwordInput && eyeIcon) {
        togglePwBtn.addEventListener("click", function () {
            const isPassword = passwordInput.type === "password";
            passwordInput.type = isPassword ? "text" : "password";
            eyeIcon.innerHTML = isPassword ? eyeClosedPath : eyeOpenPath;
            togglePwBtn.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
        });
    }

    // This checks if the login details/fields are empty 
    if (loginForm) {
        loginForm.addEventListener("submit", function (e) {
            if (emailInput.value.trim() === "" || passwordInput.value.trim() === "") {
                alert("Please fill in all fields.");
                e.preventDefault(); // This stops the user in submitting the form if its empty
            }
        });
    }
});