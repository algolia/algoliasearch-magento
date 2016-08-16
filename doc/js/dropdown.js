// Function to handle simple dropdowns
function dropdowns() {

  var openDropdown = document.querySelectorAll('[data-toggle-dropdown]');
  var otherDropdown = document.querySelectorAll('.simple-dropdown');

  for (var i = 0; i < openDropdown.length; i++) {
    toggleDropdown(openDropdown[i])
  }

  function toggleDropdown(element) {
    var dropdown = element.dataset.toggleDropdown;
    var theDropdown = document.getElementById(dropdown);

    element.addEventListener('click', function(e) {
      e.preventDefault()
      if (!theDropdown.classList.contains('opened')) {
        for (var i = 0; i < otherDropdown.length; i++) {
          otherDropdown[i].classList.remove('opened')
        }

        theDropdown.classList.add('opened');
        theDropdown.setAttribute('aria-expanded', 'true');
        this.setAttribute('aria-expanded', 'true');
      } else {
        theDropdown.classList.remove('opened');
        theDropdown.setAttribute('aria-expanded', 'false');
        this.setAttribute('aria-expanded', 'false');
      }
    });
  };
}

dropdowns();