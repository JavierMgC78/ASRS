document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('menuToggle');
    const ceaNav = document.getElementById('ceaNav');
    const header = document.getElementById('ceaHeader');

    // Toggle menú móvil hamburguesa
    if (menuToggle && ceaNav) {
        menuToggle.addEventListener('click', () => {
            ceaNav.classList.toggle('active');
            const icon = menuToggle.querySelector('i');
            if (ceaNav.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-xmark');
            } else {
                icon.classList.remove('fa-xmark');
                icon.classList.add('fa-bars');
            }
        });
    }

    // Efecto sutil en el Sticky Header al hacer scroll
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
            header.style.padding = '5px 0';
        } else {
            header.style.boxShadow = '0 2px 10px rgba(0,0,0,0.05)';
            header.style.padding = '0';
        }
    });
});