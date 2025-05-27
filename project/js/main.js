import { setRecaptchaTheme } from './recaptcha.js';
import { initArrowSlider } from './arrowSlider.js';
import { initWideSliders } from './wideSlider.js';
import { mobileMenu } from './mobileMenu.js';

document.addEventListener("DOMContentLoaded", () => {
    setRecaptchaTheme();
    initArrowSlider();
    initWideSliders();
    initFormSubmission();
    initLoginForm();
    autoFillForm(); // Автозаполнение при загрузке
    mobileMenu();
});
