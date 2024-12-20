document.addEventListener('DOMContentLoaded', function() {
    // Слайдер
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const totalSlides = slides.length;
    let autoSlideInterval;

    function showSlide(index) {
        currentSlide = (index + totalSlides) % totalSlides;
        const sliderContainer = document.querySelector('.slider-container');
        sliderContainer.style.transform = `translateX(-${currentSlide * 100}%)`;
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
        resetAutoSlide();
    }

    function prevSlide() {
        showSlide(currentSlide - 1);
        resetAutoSlide();
    }

    function startAutoSlide() {
        autoSlideInterval = setInterval(() => {
            nextSlide();
        }, 5000);
    }

    function resetAutoSlide() {
        clearInterval(autoSlideInterval);
        startAutoSlide();
    }

    // Инициализация слайдера
    showSlide(currentSlide);
    startAutoSlide();

    // Обработка подменю категорий
    const categoriesItem = document.getElementById('categories-item');
    const categoriesSubmenu = document.getElementById('categories-submenu');

    categoriesItem.addEventListener('click', function(event) {
        event.preventDefault();
        if (categoriesSubmenu.style.display === 'block') {
            categoriesSubmenu.style.display = 'none';
        } else {
            categoriesSubmenu.style.display = 'block';
        }
    });

    // Закрытие подменю при клике вне его
    document.addEventListener('click', function(event) {
        if (!categoriesItem.contains(event.target) && !categoriesSubmenu.contains(event.target)) {
            categoriesSubmenu.style.display = 'none';
        }
    });

    // Обработка панели пользователя
    const authLink = document.getElementById('auth-link');
    const userMenu = document.getElementById('user-menu');

    authLink.addEventListener('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        userMenu.style.display = userMenu.style.display === 'block' ? 'none' : 'block';
    });

    // Закрытие панели пользователя при клике вне её
    document.addEventListener('click', function(event) {
        if (!authLink.contains(event.target) && !userMenu.contains(event.target)) {
            userMenu.style.display = 'none';
        }
    });

    // Обработка формы поиска
    const searchForm = document.getElementById('search-form');

    searchForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const query = document.getElementById('search-input').value;
        window.location.href = `php/search.php?query=${encodeURIComponent(query)}`;
    });

    // Обработка фильтрации по жанру
    document.querySelectorAll('.genre-link').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const genre = this.getAttribute('data-genre');
            window.location.href = `php/search.php?genre=${encodeURIComponent(genre)}`;
        });
    });
});

function showAuthForm() {
    document.getElementById('auth-form').style.display = 'block';
}

function showLoginForm() {
    document.getElementById('register-form').style.display = 'none';
    document.getElementById('login-form').style.display = 'block';
}

function showRegisterForm() {
    document.getElementById('login-form').style.display = 'none';
    document.getElementById('register-form').style.display = 'block';
}
