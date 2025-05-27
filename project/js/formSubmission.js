export function initFormSubmission() {
<<<<<<< HEAD
  const feedbackForm = document.getElementById("form");
  const loginForm = document.getElementById("login-form");
  const successMessage = document.getElementById("success-message");
  const errorMessage = document.getElementById("error-message");
  const loginMessage = document.getElementById("login-message");
  const loginError = document.getElementById("login-error");

  if (feedbackForm) {
    feedbackForm.removeEventListener("submit", handleFormSubmit);
    feedbackForm.addEventListener("submit", handleFormSubmit);
  }

  if (loginForm) {
    loginForm.removeEventListener("submit", handleLoginSubmit);
    loginForm.addEventListener("submit", handleLoginSubmit);
  }

  async function handleFormSubmit(e) {
    e.preventDefault();
    successMessage.style.display = "none";
    errorMessage.style.display = "none";
    loginMessage.style.display = "none";
    loginForm.style.display = "none";

    const formData = new FormData(feedbackForm);
    const data = Object.fromEntries(formData);
    data.languages = formData.getAll("languages[]");

    const errors = validateForm(data);
    if (Object.keys(errors).length > 0) {
      errorMessage.textContent = Object.values(errors).join(", ");
      errorMessage.style.display = "block";
      return;
    }

    const isAuthenticated = !!sessionStorage.getItem("user");
    const url = "/api.php";
    const method = isAuthenticated ? "PUT" : "POST";
    const action = isAuthenticated
      ? {
          action: "update",
          id: sessionStorage.getItem("application_id"),
          login: sessionStorage.getItem("user"),
        }
      : { action: "create" };

    try {
      const response = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...data, ...action }),
      });

      const result = await response.json();

      if (response.ok) {
        successMessage.style.display = "block";
        feedbackForm.reset();
        if (!isAuthenticated) {
          loginMessage.textContent = `Логин: ${result.login}, Пароль: ${result.password}, Профиль: ${result.profile_url}`;
          loginMessage.style.display = "block";
          sessionStorage.setItem("user", result.login);
          sessionStorage.setItem("application_id", result.application_id);
          loginForm.style.display = "block"; // Показываем форму авторизации
        } else {
          loginMessage.textContent = "Данные успешно обновлены!";
          loginMessage.style.display = "block";
        }
      } else {
        errorMessage.textContent = result.errors
          ? Object.values(result.errors).join(", ")
          : `Ошибка сервера: ${response.status} ${response.statusText}`;
        errorMessage.style.display = "block";
      }
    } catch (error) {
      errorMessage.textContent = "Ошибка сети: " + error.message;
      errorMessage.style.display = "block";
    }
  }

  async function handleLoginSubmit(e) {
    e.preventDefault();
    loginError.style.display = "none";
    const formData = new FormData(loginForm);
    const data = Object.fromEntries(formData);

    try {
      const response = await fetch("/api.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "login",
          login: data.login,
          pass: data.pass,
        }),
      });

      const result = await response.json();

      if (response.ok) {
        sessionStorage.setItem("user", result.login);
        sessionStorage.setItem("application_id", result.application_id);
        loginForm.style.display = "none";
        loginMessage.textContent =
          "Авторизация успешна! Теперь вы можете редактировать данные.";
        loginMessage.style.display = "block";

        // Загружаем данные пользователя
        await loadUserData(result.application_id);
      } else {
        loginError.textContent = result.error || "Неверный логин или пароль";
        loginError.style.display = "block";
      }
    } catch (error) {
      loginError.textContent = "Ошибка сети: " + error.message;
      loginError.style.display = "block";
    }
  }

  async function loadUserData(applicationId) {
    try {
      const response = await fetch(
        `/api.php?action=get&id=${applicationId}`,
        {
          method: "GET",
          headers: { "Content-Type": "application/json" },
        }
      );

      const result = await response.json();

      if (response.ok) {
        // Заполняем форму данными пользователя
        document.getElementById("fio").value = result.fio;
        document.getElementById("tel").value = result.phone;
        document.getElementById("email").value = result.email;
        document.getElementById("bio").value = result.bio;
        document.getElementById("contract").checked = result.contract;

        const languagesSelect = document.getElementById("languages");
        Array.from(languagesSelect.options).forEach((option) => {
          option.selected = result.languages.includes(option.value);
        });
      } else {
        errorMessage.textContent = result.error || "Ошибка загрузки данных";
        errorMessage.style.display = "block";
      }
    } catch (error) {
      errorMessage.textContent = "Ошибка сети: " + error.message;
      errorMessage.style.display = "block";
    }
  }

  function validateForm(data) {
    const errors = {};
    if (!data.fio || !/^[a-zA-Zа-яА-Я\s]{1,150}$/u.test(data.fio)) {
      errors.fio = "Некорректное ФИО";
    }
    if (!data.phone || !/^\+?\d{10,15}$/.test(data.phone)) {
      errors.phone = "Некорректный телефон";
    }
    if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
      errors.email = "Некорректный email";
    }
    if (!data.bio) {
      errors.bio = "Заполните биографию";
    }
    if (!data.contract) {
      errors.contract = "Необходимо согласиться с условиями";
    }
    return errors;
  }
}

document.addEventListener("DOMContentLoaded", initFormSubmission);
=======
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
                errorMessage.textContent = 'Ошибка сети. Попробуйте снова.';
                errorMessage.style.display = 'block';
                console.error('Ошибка:', error);
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
>>>>>>> e73591a260ce11a858a5dd40429c844ad9f7abf7
