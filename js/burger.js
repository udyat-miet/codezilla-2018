toggle = document.querySelectorAll('.toggle')[0];
nav = document.querySelectorAll('nav')[0];
toggle_open_text = 'CZ18';
toggle_close_text = 'Close';

toggle.addEventListener(
  'click',
  function() {
    nav.classList.toggle('open');

    if (nav.classList.contains('open')) {
      toggle.innerHTML = toggle_close_text;
    } else {
      toggle.innerHTML = toggle_open_text;
    }
  },
  false
);

setTimeout(function() {
  nav.classList.toggle('open');
}, 800);
