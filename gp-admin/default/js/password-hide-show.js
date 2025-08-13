const PasswordField = document.querySelector(".loginform input[type='password']");
const ToggleButton = document.querySelector(".loginform .nfield .icon");

ToggleButton.addEventListener('click', function () {
    // alert('Done!');
    if (PasswordField.type == 'password') {
        PasswordField.type = 'text';
        ToggleButton.classList.add('active');
    } else {
        PasswordField.type = 'password';
        ToggleButton.classList.remove('active');
    }
});