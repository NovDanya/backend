export function initFormSubmission() {
	const feedbackForm = document.getElementById('form');
	const successMessage = document.getElementById('success-message');
	const errorMessage = document.getElementById('error-message');
  
	if (feedbackForm) {
	  feedbackForm.addEventListener('submit', (e) => {
		e.preventDefault();
		
		successMessage.style.display = 'none';
		errorMessage.style.display = 'none';
  
		const formData = new FormData(feedbackForm);
  
		fetch('https://formcarry.com/s/m1h-mKEGfLX', {
		  method: 'POST',
		  headers: {
			'Accept': 'application/json',
		  },
		  body: formData
		})
		.then(response => {
		  if (response.ok) {
			successMessage.style.display = 'block';
			errorMessage.style.display = 'none';
			feedbackForm.reset();
		  } else {
			successMessage.style.display = 'none';
			errorMessage.style.display = 'block';
		  }
		})
		.catch(error => {
		  successMessage.style.display = 'none';
		  errorMessage.style.display = 'block';
		  console.error('Ошибка:', error);
		});
	  });
	}
  }
  
  