document.querySelector('form').addEventListener('submit', function(e) {
    const pass = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;

    if (pass !== confirm) {
        e.preventDefault(); // Останавливаем отправку формы
        alert('Пароли не совпадают!'); // Или выведи красивое уведомление
        document.getElementById('password_confirm').style.borderColor = 'red';
    }
});