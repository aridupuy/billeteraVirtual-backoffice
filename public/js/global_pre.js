//CARROUSELES-SLIDERS
function carrouselWeb(){

  $('.carrousel-herramientas').slick({
      autoplay: true,
      slidesToShow: 3,
      slidesToScroll: 1,
      infinite: true,
      arrows: true,
      dots: false,
      responsive: [
      {
        breakpoint: 800,
        settings: {
          slidesToShow: 2,
        }
      },
      {
        breakpoint: 600,
        settings: {
          slidesToShow: 1,
        }
      }
    ]
  });
  $('.carrousel-logos').slick({
      infinite: true,
      slidesToShow: 12,
      slidesToScroll: 1,
      autoplay: true,
      arrows: false,
      dots: false,
      responsive: [
      {
        breakpoint: 800,
        settings: {
          slidesToShow: 6,
        }
      },
      {
        breakpoint: 600,
        settings: {
          slidesToShow: 3,
        }
      }
      ]
  });

}

//SCROLL LINKS
function scrollLinks() {
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

//STICKY HEADER
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

function itemsShowup() {
    if ($(window).width() < 600) {
      $(window).scroll(function() {
          var t = $(this).scrollTop();
          $(".bottom-up, .left-right, .right-left, .zoom-in").each(function() {
              t > $(this).offset().top - 300 ? $(this).addClass("showing") : t < $(this).offset().top - 900 && $(this).removeClass("showing")
          })
      })
    } else {
      $(window).scroll(function() {
          var t = $(this).scrollTop();
          $(".bottom-up, .left-right, .right-left, .zoom-in").each(function() {
              t > $(this).offset().top - 300 ? $(this).addClass("showing") : t < $(this).offset().top - 900 && $(this).removeClass("showing")
          })
      })
    }    
}

//MENU DESPLEGABLE
function desplegableMobile() {
  var contador = 1;

  $('.hamburger').click(function() {
    if (contador == 1) {
      $('nav').addClass('active');
      $('.bar').addClass('active');
      contador = 0;
    } else {
      contador = 1;
      $('nav').removeClass('active');
      $('.bar').removeClass('active');
    }
  });

  //CERRAR MENU DESPLEGABLE
  if ($(window).width() < 800) {
    $('nav .anchor').click(function() {
      if ($('nav').hasClass('active')){
        contador = 1;
        $('nav').removeClass('active');
        $('.bar').removeClass('active');
      }
    });
  }
}

//ANIMACIÓN MEDIOS DE PAGO
function animacionMedios() {
  $('.medio-pago').each(function(i){
    $(window).on('scroll',function(){
      var scrollVentana = $(window).scrollTop();
      var alturaMedios = $('.medios-pago').offset().top;
      var triggerMedios = (alturaMedios - scrollVentana);

      if ( triggerMedios <= 240){
        setTimeout(function(){
          $('.medio-pago .icono-medio').eq(i).addClass('showing');
          $('.medio-pago .titulo-medio').eq(i).addClass('showing');
          $('.medio-pago .bajada-medio').eq(i).addClass('showing');
          }, 200 * (i+1));
      } 

      if ( triggerMedios > 400){
          $('.medio-pago .icono-medio').removeClass('showing');
          $('.medio-pago .titulo-medio').removeClass('showing');
          $('.medio-pago .bajada-medio').removeClass('showing');
      }
    });
  });     
}

//DELAY CARGA
function showLoad() {
  $(".carrousel-logos, .carrousel-herramientas").delay(3000).addClass('active');
}

//POP UPS
function controlPopups() {
  $('.trigger-video').click(function() {
      $('#popup-video').addClass('active');
      setTimeout( function(){
        $('#popup-video').css({'opacity' : '1','transform' : 'scale(1)'});
      },500);
      $('header').css({'z-index' : '4000'});
  });

  $('.trigger-login').click(function() {
      $('#popup-login').addClass('active');
      setTimeout( function(){
        $('#popup-login').css({'opacity' : '1','transform' : 'scale(1)'});
      },500);
      $('header').css({'z-index' : '4000'});
  });

  $('.cerrar-popup').click(function() {
    var stopVideos = function () {
      var videos = document.querySelectorAll('iframe, video');
      Array.prototype.forEach.call(videos, function (video) {
        if (video.tagName.toLowerCase() === 'video') {
          video.pause();
        } else {
          var src = video.src;
          video.src = src;
        }
      });
    };
    stopVideos();
    // var leg=$('.video-popup iframe').attr("src");
    // $('.video-popup iframe').attr("src",leg);
    $('header').css({'z-index' : '7000'});
    $(this).parents('.popup').removeClass('active');
    $(this).parents('.popup').css({'opacity' : '0','transform' : 'scale(1.2)'});
  }); 
}


$(document).ready(function () {
    carrouselWeb();
    scrollLinks();
    stickyHeader();
    itemsShowup();
    desplegableMobile();
    animacionMedios();
    showLoad();
    controlPopups();
});



    