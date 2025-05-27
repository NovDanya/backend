import { setRecaptchaTheme } from './recaptcha.js';
import { initArrowSlider } from './arrowSlider.js';
import { initWideSliders } from './wideSlider.js';
<<<<<<< HEAD
import { initFormSubmission } from './formSubmission.js';
import {mobileMenu} from './mobileMenu.js';
=======
import { mobileMenu } from './mobileMenu.js';

>>>>>>> e73591a260ce11a858a5dd40429c844ad9f7abf7
document.addEventListener("DOMContentLoaded", () => {
  setRecaptchaTheme();
  initArrowSlider();
  initWideSliders();
  initFormSubmission();
  mobileMenu();
});

