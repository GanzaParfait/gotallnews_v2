const form = document.getElementById('newformdata');
const SendBtn = document.getElementById('formdatabtn');
const msgText = document.getElementById('msg');

form.onsubmit = (e) => {
    e.preventDefault();


    SendBtn.onclick = () => {
        let xhr = new XMLHttpRequest(); // create new xml object
        xhr.open("POST", "php/new_category.php", true);

        xhr.onload = () => { // once ajax loaded
            if (xhr.readyState == 4 && xhr.status == 200) {
                let data = xhr.response;
                // console.log(data);

                if (data == 'New category added successfully!') {
                    msgText.style.display = 'block';
                    msgText.textContent = data;
                    form.reset();
                } else {
                    msgText.style.display = 'block';
                    msgText.textContent = data;
                }

                // console.log(data)

            }
        }
        let formData = new FormData(form);
        xhr.send(formData);

    }

}

// console.warn = ()=> {};

