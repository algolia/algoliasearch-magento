
function sidebarFollowScroll(sidebarContainer) {
  console.log(sidebarContainer + ' > ul')
  var positionSidebar = function positionSidebar() {
    var hero = document.querySelector('.header-section');
    var footer = document.querySelector('.ac-footer');
    var spacer = document.querySelector('.spacer100');
    var navigation = document.querySelector('.ac-nav');
    var menu = document.querySelector('.menu');
    var heroHeight = hero.offsetHeight;
    var navHeight = navigation.offsetHeight;
    var height = document.querySelector('html').getBoundingClientRect().height;
    var footerHeight = footer.offsetHeight + spacer.offsetHeight;
    var menuHeight = menu.offsetHeight;
    var currentScroll = window.pageYOffset;
    var doc = document.querySelector('.main-content');
    var paddingDoc = 20;
    if (currentScroll > ((heroHeight - navHeight) + paddingDoc)) {
      var fold = height - footerHeight - menuHeight - paddingDoc - 100;
      if (currentScroll > fold) {
        sidebarContainer.style.top = (fold - currentScroll) + 'px';
      } else {
        sidebarContainer.style.top = null;
      }
      sidebarContainer.classList.add('fixed');
    } else {
      sidebarContainer.classList.remove('fixed');
    }
  };
  window.addEventListener('load', positionSidebar);
  document.addEventListener('DOMContentLoaded', positionSidebar);
  document.addEventListener('scroll', positionSidebar);
}

sidebarFollowScroll(document.querySelector('.menu'))

