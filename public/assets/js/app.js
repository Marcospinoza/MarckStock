document.addEventListener('click', (e) => {
  const trigger = e.target.closest('[data-dropdown]');
  document.querySelectorAll('.dropdown-menu.open').forEach(menu => {
    if (!trigger || menu.id !== trigger.dataset.dropdown) menu.classList.remove('open');
  });
  if (trigger) {
    e.preventDefault();
    const menu = document.getElementById(trigger.dataset.dropdown);
    if (menu) menu.classList.toggle('open');
  }
  const sidebarBtn = e.target.closest('[data-toggle-sidebar]');
  if (sidebarBtn) document.body.classList.toggle('sidebar-open');
});
