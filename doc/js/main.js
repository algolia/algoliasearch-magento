function checkVisible(elm, threshold, mode) {
    threshold = threshold || 0;
    mode = mode || 'visible';
    var rect = elm.getBoundingClientRect();
    var viewHeight = Math.max(document.documentElement.clientHeight, window.innerHeight);
    var above = rect.bottom - threshold < 0;
    var below = rect.top - viewHeight + threshold >= 0;
    return mode === 'above' ? above : (mode === 'below' ? below : !above && !below);
}

// Now enable the animations only when in the section
var dist = 0;
var demos = document.querySelector('.demos');

window.onscroll = function() {

 // Animation what is a record
 if (checkVisible(demos, dist)) {
   demos.classList.add('animated');
 } else {
   demos.classList.remove('animated');
 }
};

// To insert on line #55
function animOnVisibility() {
  checkVisible(demos, dist);
}

window.onload = function() {
    checkVisible(demos, dist);
}