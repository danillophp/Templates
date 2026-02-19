document.addEventListener('DOMContentLoaded', () => {
  const menuLinks = document.querySelectorAll('.site-nav__list a');
  menuLinks.forEach((link) => {
    link.addEventListener('focus', () => {
      link.style.outline = '2px solid #a66c85';
      link.style.outlineOffset = '4px';
    });
    link.addEventListener('blur', () => {
      link.style.outline = 'none';
    });
  });
});
