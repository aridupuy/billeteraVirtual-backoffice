//MENU DESPLEGABLE
$(function desplegableMobile() {
  var contador = 1;

  $('.hamburger').click(function() {
    if (contador == 1) {
      $('.sidebar').css('left','0');

      contador = 0;
    } else {
      contador = 1;
      $('.sidebar').css('left','-300px');
 
      
    }
  });
});
