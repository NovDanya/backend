function initSlider(wrapper, direction) {
	const slides = Array.from(wrapper.querySelectorAll('.swiper-slide'));
	const slideWidth = slides[0].offsetWidth;
	
	wrapper.innerHTML = '';
	for (let i = 0; i < 3; i++) {
	  slides.forEach(slide => {
		wrapper.appendChild(slide.cloneNode(true));
	  });
	}
	
	const totalWidth = slideWidth * slides.length;
	let position = direction === 'left' ? 0 : -totalWidth;
	const speed = 0.5;
	
	function animate() {
	  position += direction === 'left' ? -speed : speed;
	  
	  if (direction === 'left' && Math.abs(position) >= totalWidth) {
		position = 0;
	  } else if (direction === 'right' && position >= 0) {
		position = -totalWidth;
	  }
	  
	  wrapper.style.transform = `translateX(${position}px)`;
	  requestAnimationFrame(animate);
	}
	
	animate();
  }
  
  export function initWideSliders() {
	const topWrapper = document.querySelector('.swiper-wrapper:first-child');
	const bottomWrapper = document.querySelector('.swiper-wrapper:last-child');
  
	if (topWrapper) initSlider(topWrapper, 'left');
	if (bottomWrapper) initSlider(bottomWrapper, 'right');
  }
  
  