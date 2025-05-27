import { setRecaptchaTheme } from './recaptcha.js';
import { initArrowSlider } from './arrowSlider.js';
import { initWideSliders } from './wideSlider.js';
import { mobileMenu } from './mobileMenu.js';

export function initFormSubmission() {
    const feedbackForm = document.getElementById('contact-form');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');
    const credentialsMessage = document.getElementById('credentials-message');
    const generatedLogin = document.getElementById('generated-login');
    const generatedPassword = document.getElementById('generated-password');

    if (feedbackForm) {
        feedbackForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';
            credentialsMessage.style.display = 'none';

            const fio = document.getElementById('fio').value.trim();
            const phone = document.getElementById('tel').value.trim();
            const email = document.getElementById('email').value.trim();
            const comment = document.getElementById('comment').value.trim();
            const agree = document.querySelector('.label__input').checked;

            const errors = [];
            if (!fio) errors.push('ФИО обязательно');
            if (!phone.match(/^\+?\d{10,15}$/)) errors.push('Некорректный телефон');
            if (!email.match(/^[\w-.]+@([\w-]+\.)+[\w-]{2,4}$/)) errors.push('Некорректный email');
            if (!agree) errors.push('Необходимо согласие на обработку данных');

            if (errors.length > 0) {
                errorMessage.textContent = errors.join(', ');
                errorMessage.style.display = 'block';
                return;
            }

            try {
                const token = localStorage.getItem('authToken');
                const headers = { 'Content-Type': 'application/json' };
                if (token) headers['Authorization'] = token;

                const response = await fetch('/api.php', {
                    method: token ? 'PUT' : 'POST',
                    headers,
                    body: JSON.stringify({ fio, phone, email, comment, agree: true })
                });
                console.log('Status:', response.status);
                console.log('Headers:', Object.fromEntries(response.headers));
                const text = await response.text();
                console.log('Response text:', text.substring(0, 200)); // Первые 200 символов
                const result = await response.json();

                if (response.ok) {
                    if (token) {
                        successMessage.textContent = result.message;
                        successMessage.style.display = 'block';
                    } else {
                        successMessage.textContent = 'Спасибо за заявку!';
                        successMessage.style.display = 'block';
                        credentialsMessage.style.display = 'block';
                        generatedLogin.textContent = `Логин: ${result.login}`;
                        generatedPassword.textContent = `Пароль: ${result.password}`;
                    }
                    feedbackForm.reset();
                } else {
                    errorMessage.textContent = result.errors ? result.errors.join(', ') : 'Произошла ошибка';
                    errorMessage.style.display = 'block';
                }
            } catch (error) {
              console.error('Fetch error:', error);
              errorMessage.textContent = `Ошибка сети: ${error.message}`;
              errorMessage.style.display = 'block';
            }
        });
    }
}

function initLoginForm() {
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');
    const feedbackForm = document.getElementById('contact-form');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');
    const credentialsMessage = document.getElementById('credentials-message');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            loginError.style.display = 'none';
            successMessage.style.display = 'none';
            errorMessage.style.display = 'none';
            credentialsMessage.style.display = 'none';

            const login = document.getElementById('login').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!login || !password) {
                loginError.textContent = 'Введите логин и пароль';
                loginError.style.display = 'block';
                return;
            }

            const token = `${login}:${password}`;
            try {
                const response = await fetch('/api.php', {
                    method: 'GET',
                    headers: { 'Authorization': token }
                });
                const result = await response.json();

                if (response.ok) {
                    localStorage.setItem('authToken', token);
                    if (feedbackForm) {
                        document.getElementById('fio').value = result.fio;
                        document.getElementById('tel').value = result.phone;
                        document.getElementById('email').value = result.email;
                        document.getElementById('comment').value = result.comment || '';
                    }
                    loginForm.reset();
                    successMessage.textContent = 'Вход успешен. Редактируйте форму.';
                    successMessage.style.display = 'block';
                } else {
                    loginError.textContent = result.error || 'Ошибка входа';
                    loginError.style.display = 'block';
                }
            } catch (error) {
                loginError.textContent = 'Ошибка сети';
                loginError.style.display = 'block';
                console.error('Ошибка:', error);
            }
        });
    }
}

// Автозаполнение формы при загрузке, если пользователь авторизован
function autoFillForm() {
    const token = localStorage.getItem('authToken');
    if (token) {
        fetch('/api.php', {
            method: 'GET',
            headers: { 'Authorization': token }
        })
            .then(response => response.json())
            .then(result => {
                if (result.fio) {
                    document.getElementById('fio').value = result.fio;
                    document.getElementById('tel').value = result.phone;
                    document.getElementById('email').value = result.email;
                    document.getElementById('comment').value = result.comment || '';
                }
            })
            .catch(error => console.error('Ошибка автозаполнения:', error));
    }
}

document.addEventListener("DOMContentLoaded", () => {
    setRecaptchaTheme();
    initArrowSlider();
    initWideSliders();
    initFormSubmission();
    initLoginForm();
    autoFillForm(); // Автозаполнение при загрузке
    mobileMenu();
});
