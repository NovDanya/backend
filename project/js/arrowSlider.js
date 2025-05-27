export function initArrowSlider() {
	const slides = document.querySelectorAll(".slide");
	let currentIndex = 0;
	let slideInterval;
  
	function showSlide(index, direction) {
	  const currentSlide = slides[currentIndex];
	  const nextSlide = slides[index];
	
	  if (direction === 'next') {
		currentSlide.classList.add('slideOutLeft');
		nextSlide.classList.add('slideInRight');
	  } else {
		currentSlide.classList.add('slideOutRight');
		nextSlide.classList.add('slideInLeft');
	  }
	
	  setTimeout(() => {
		slides.forEach((slide) => {
		  slide.classList.remove('active', 'slideOutLeft', 'slideOutRight', 'slideInLeft', 'slideInRight');
		  slide.style.display = "none";
		});
	
		nextSlide.classList.add('active');
		nextSlide.style.display = "block";
	
	  }, 500);
	
	  currentIndex = index;
	
	  const currentSlideNumber = (index + 1).toString().padStart(2, "0");
	  document.querySelector(".ednum").textContent = currentSlideNumber;
	}
	
	function nextSlide() {
	  const newIndex = (currentIndex + 1) % slides.length;
	  showSlide(newIndex, 'next');
	}
	
	function prevSlide() {
	  const newIndex = (currentIndex - 1 + slides.length) % slides.length;
	  showSlide(newIndex, 'prev');
	}
	
	function startAutoSlide() {
	  slideInterval = setInterval(nextSlide, 5000);
	}
	
	function stopAutoSlide() {
	  clearInterval(slideInterval);
	}
	
	document.querySelector(".slider__next").addEventListener("click", () => {
	  nextSlide();
	  stopAutoSlide();
	  startAutoSlide();
	});
	
	document.querySelector(".slider__prev").addEventListener("click", () => {
	  prevSlide();
	  stopAutoSlide();
	  startAutoSlide();
	});
	
	slides[currentIndex].classList.add('active');
	slides[currentIndex].style.display = "block";
	startAutoSlide();
  }
  
  