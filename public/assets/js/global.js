//MENU DESPLEGABLE
$(function desplegableMobile() {
  var contador = 1;

  $('.hamburger').click(function () {
    if (contador == 1) {
      $('.sidebar').css('left', '0');

      contador = 0;
    } else {
      contador = 1;
      $('.sidebar').css('left', '-300px');


    }
  });
});
$(function triggerPopup() {
  $('.trigger-popup').click(function () {
    $('.popup-wrapper').toggleClass("active");
  })
})
$('.popup-wrapper').click((event) => {
  if ($('.popup-wrapper').hasClass("active")) {
    if (!$(event.target).closest('.popup').length) {
      $('.popup-wrapper').removeClass("active");

    }
  }
});