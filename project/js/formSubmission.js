export function initFormSubmission() {
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
    const url = "/8LAB/api.php";
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
      const response = await fetch("/8LAB/api.php", {
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
        `/8LAB/api.php?action=get&id=${applicationId}`,
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
        document.getElementById("dob").value = result.dob;
        document.getElementById("gender").value = result.gender;
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
    if (!data.dob) {
      errors.dob = "Некорректная дата рождения";
    }
    if (!data.gender || !["male", "female"].includes(data.gender)) {
      errors.gender = "Выберите пол";
    }
    if (
      !data.languages ||
      !Array.isArray(data.languages) ||
      data.languages.length === 0
    ) {
      errors.languages = "Выберите хотя бы один язык";
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
