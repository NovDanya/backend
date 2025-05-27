export function mobileMenu()  {
    const burgerBtn = document.querySelector('.burger-btn');
    const mobileNav = document.querySelector('.mobile-nav');
    const menuItems = document.querySelectorAll('.has-submenu');

    burgerBtn.addEventListener('click', () => {
        mobileNav.classList.toggle('active');
        document.body.classList.toggle('menu-open');

        // Animate burger button
        burgerBtn.classList.toggle('active');
        burgerBtn.querySelectorAll('span').forEach((span, index) => {
            if (burgerBtn.classList.contains('active')) {
                if (index === 0) span.style.transform = 'rotate(45deg) translate(5px, 5px)';
                if (index === 1) span.style.opacity = '0';
                if (index === 2) span.style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                span.style.transform = 'none';
                span.style.opacity = '1';
            }
        });
    });

    menuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            if (e.target.tagName === 'SPAN') {
                e.preventDefault();
                item.classList.toggle('open');
            }
        });
    });

	document.addEventListener('click', (e) => {
        if (!mobileNav.contains(e.target) && !burgerBtn.contains(e.target) && mobileNav.classList.contains('active')) {
            mobileNav.classList.remove('active');
            burgerBtn.classList.remove('active');
            burgerBtn.querySelectorAll('span').forEach(span => {
                span.style.transform = 'none';
                span.style.opacity = '1';
            });
        }
    });
	function checkWindowSize() {
        if (window.innerWidth > 768 && mobileNav.classList.contains('active')) {
            mobileNav.classList.remove('active');
            burgerBtn.classList.remove('active');
            burgerBtn.querySelectorAll('span').forEach(span => {
                span.style.transform = 'none';
                span.style.opacity = '1';
            });
        }
    }

    window.addEventListener('resize', checkWindowSize);
    checkWindowSize(); 

  }