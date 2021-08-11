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
  $(".principal .dropdown").click(function () {
    $(this).next().toggleClass("active")
  })
});
$(function triggerPopup() {
  $('.trigger-popup').click(function () {
    $('.popup-wrapper').toggleClass("active");
  })
  $('.trigger-popup-agregar').click(function () {
    $('.popup-wrapper.agregar').toggleClass("active");
  })
  $('.trigger-popup-vermas').click(function () {

    $('.popup-wrapper.vermas').toggleClass("active");
  })
  $('.trigger-popup-editar').click(function () {

    $('.popup-wrapper.editar').toggleClass("active");
  })
})
$('.popup-wrapper').click((event) => {
  if ($('.popup-wrapper').hasClass("active")) {
    if (!$(event.target).closest('.popup').length) {
      $('.popup-wrapper').removeClass("active");

    }
  }
});