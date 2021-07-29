//MENU DESPLEGABLE
$(function desplegableMobile() {
  var contador = 1;

  $('.hamburger').click(function () {
    if (contador == 1) {
      $('.sidebar').css('transform', 'translateX(0%');

      contador = 0;
    } else {
      contador = 1;
      $('.sidebar').css('transform', 'translateX(-100%)');


    }
  });
  $('.cerrar-menu').click(function () {
    $('.sidebar').css('transform', 'translateX(-100%)');
    contador = 1;
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