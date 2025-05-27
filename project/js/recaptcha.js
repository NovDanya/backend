export function setRecaptchaTheme() {
	const recaptcha = document.querySelector(".g-recaptcha");
	if (recaptcha) {
	  recaptcha.setAttribute("data-theme", "dark");
	}
  }
  
  