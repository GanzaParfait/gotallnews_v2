const form = document.querySelector('.loginform');
const loginBtn = form.querySelector('.loginbtn');
const errorText = document.querySelector('.error-msg');


form.onsubmit = (e) => {
    e.preventDefault();
}


loginBtn.onclick = () => {
    let xhr = new XMLHttpRequest(); // create new xml object
    xhr.open("POST", "php/login.php", true);

    xhr.onload = () => { // once ajax loaded
        if (xhr.readyState == 4 && xhr.status == 200) {
            let response = xhr.response;
            // console.log(response);
            function myFunction(x) {
                if (x.matches) { // If media query matches
                    if (response == 'success') {
                        location.href = '../admin/index.php';
                    } else {
                        alert(response);
                    }
                } else {
                    if (response == 'success') {
                        location.href = '../admin/index.php';
                    } else {
                        errorText.textContent = response;

                        if (response === 'Wrong Password' || response === 'All inputs are required' || response === 'Incorrect Phone number') {

                            errorText.style.background = 'red';

                        } else {
                            errorText.style.background = '#08df08';
                        }

                        errorText.style.display = 'block';

                        setTimeout(() => {
                            errorText.style.display = 'none';
                        }, 5000);
                    }
                }
            }

            var x = window.matchMedia("(max-width: 400px)")
            myFunction(x) // Call listener function at run time
            x.addListener(myFunction) // Attach listener function on state changes
        }
    }
    let formData = new FormData(form);
    xhr.send(formData);
}
