document.querySelector('form').addEventListener('submit', function(e) {
    const pass = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;

    if (pass !== confirm) {
        e.preventDefault();
        alert('Пароли не совпадают!');
        document.getElementById('password_confirm').style.borderColor = 'red';
    }
});