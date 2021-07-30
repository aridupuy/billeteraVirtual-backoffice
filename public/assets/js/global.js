//MENU DESPLEGABLE
$(function desplegableMobile() {
  $('.hamburger').click(function () {
    $('.sidebar').toggleClass("active");
  });
  $('.cerrar-menu').click(function () {
    $('.sidebar').toggleClass("active");
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