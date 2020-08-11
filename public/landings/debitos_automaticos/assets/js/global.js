function stickyHeader() {
    var altura = $('body').offset().top;

    $(window).on('scroll', function(){
        if ( $(window).scrollTop() > altura ){
            $('header').addClass('sticky'),
            $('.logo').addClass('sticky');
        } else {
            $('header').removeClass('sticky'),
            $('.logo').removeClass('sticky');                     
        }
    });
}

//ARRASTRE A SECCIONES
function arrastreSecciones() {
  jQuery(document).on('click', '.anchor a', function(event){
    event.preventDefault();

    jQuery('html, body').animate({
        scrollTop: jQuery( jQuery.attr(this, 'href') ).offset().top
    }, 500),
    setTimeout(function() {
        $('.menu.sticky').slideUp("slow");
    }, 1000);
});
}

//MENU DESPLEGABLE MOBILE
function triggerForms() {

  $('.trigger-contacto').click(function() {
      $('.form-contacto').toggleClass('active');
  });
  $('.trigger-asociarme').click(function() {
      $('.form-asociarse').toggleClass('active');
  });

//CERRAR MENU DESPLEGABLE MOBILE
  $('.cerrar-form').click(function() {
    $(this).parents('.form-box').toggleClass('active');
  });
}

//MOSTRAR FLECHA TOP
function flechaTop() {
    $(".flecha-top").hide();
    $(window).scroll(function() {
        var t = $(this).scrollTop();
        $(".flecha-top").each(function() {
            t > $('.portada .cta').offset().top ? $(this).fadeIn("slow") : t > $('.portada .cta').offset().top - 300 && $(this).fadeOut("slow")
        })
    })
}

//CARROUSELES
function carrouselWeb(){
  if ($(window).width() < 600) {
    $('.contenedor-beneficios').slick({
      infinite: true,
      slidesToShow: 1,
      slidesToScroll: 1,
      autoplay: true,
      arrows: false,
      dots: true,
    });
  }
}

$(document).ready(function () {
    stickyHeader();
    arrastreSecciones();
    triggerForms();
    flechaTop();
    carrouselWeb();
});