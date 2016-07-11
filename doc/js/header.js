
$(document).ready(function(){

  $('#doc-search').on('keyup', function(){
    if($(this).val()) {
      $('.searchbox__reset').removeClass('hide')
    }
  }).on('blur', function(){
      $('.searchbox__reset').addClass('hide')
  });

  function responsiveNavigation() {
    var navigation = document.querySelector('.ac-nav');
    var links = navigation.querySelectorAll('a');
    var navigationAsSelect = document.createElement('select');

    if (navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/iPod/i)) {
      navigationAsSelect.classList.add('display-on-small', 'device');
    } else {
      navigationAsSelect.classList.add('display-on-small');
    }

    for (var i = 0; i < links.length; i++) {
      var option = document.createElement('option');
      option.text = links[i].title;
      option.value = links[i].href;
      option.selected = true;
      navigationAsSelect.appendChild(option);
    }

    navigation.appendChild(navigationAsSelect);
    navigation.addEventListener('change', function () {
      return window.location = e.target.value;
    });
  }
  responsiveNavigation();
});