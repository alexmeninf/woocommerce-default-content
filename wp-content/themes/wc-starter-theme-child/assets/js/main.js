jQuery(function ($) {
  maskInput = () => {
    // $("input[type=tel], .mask-phone").on("keyup", function () {
    //   if ($(this).val().length >= 15) {
    //     $(this).mask("(00) 0 0000-0000");
    //   } else {
    //     $(this).mask("(00) 0000-00009");
    //   }
    // }).trigger("keyup");

    // CEP
    $("input[name=billing_postcode], input[name=shipping_postcode]").mask(
      "00000-000"
    );

    // CNPJ
    $("input#pj_cnpj").mask("00.000.000/0000-00");

    // CPF
    $("input#pf_cpf").mask("000.000.000-00");
  };

  smoothClick = (duration = 1200) => {
    // smooth scroll
    $('a[href*="#"]')
      .not('[href="#"]')
      .not('[href*="#tab-"]')
      .not('[href="#0"]')
      .click(function (event) {
        if (
          location.pathname.replace(/^\//, "") ==
          this.pathname.replace(/^\//, "") &&
          location.hostname == this.hostname
        ) {
          var target = $(this.hash);
          target = target.length
            ? target
            : $("[name=" + this.hash.slice(1) + "]");

          if (target.length) {
            event.preventDefault();
            $("html, body").animate(
              {
                scrollTop: target.offset().top,
              },
              duration
            );
          }
        }
      });
  };

  owlCarousel = () => {
    "use strict";

    $(".owl-carousel").each(function () {
      let config = {
        dots: false,
        dotsSpeed: 400,
        navText: [
          '<i class="fal fa-chevron-left icon"></i>',
          '<i class="fal fa-chevron-right icon"></i>',
        ],
      };
      let owl = $(this);

      if (parseInt($(this).attr("data-padding"), 10) > 0) {
        config.stagePadding = parseInt($(this).attr("data-padding"), 10);
      }

      if ($(this).attr("data-dots") == "true") {
        config.dots = true;
      }

      if (parseInt($(this).attr("data-margin"), 10) > 0) {
        config.margin = parseInt($(this).attr("data-margin"), 10);
      }

      if ($(this).attr("data-autoplay") == "true") {
        config.autoplay = true;

        if (parseInt($(this).attr("data-timeout"), 10) > 0) {
          config.autoplayTimeout = parseInt($(this).attr("data-timeout"), 10);
        }
        if ($(this).attr("data-hoverPause") == "true") {
          config.autoplayHoverPause = true;
        }
      }

      if ($(this).attr("data-loop") == "true") {
        config.loop = true;
      }

      if ($(this).attr("data-nav") == "true") {
        config.nav = true;
      }

      let responsive_config = {};

      if ($(this).attr("data-xxs")) {
        responsive_config.itemsXSmall = parseInt($(this).attr("data-xxs"), 10);
      } else {
        responsive_config.itemsXSmall = 1;
      }

      if ($(this).attr("data-xs")) {
        responsive_config.itemsMobile = parseInt($(this).attr("data-xs"), 10);
      } else {
        responsive_config.itemsMobile = responsive_config.itemsXSmall;
      }

      if ($(this).attr("data-sm")) {
        responsive_config.itemsTabletSmall = parseInt(
          $(this).attr("data-sm"),
          10
        );
      } else {
        responsive_config.itemsTabletSmall = responsive_config.itemsMobile;
      }

      if ($(this).attr("data-md")) {
        responsive_config.itemsTablet = parseInt($(this).attr("data-md"), 10);
      } else {
        responsive_config.itemsTablet = responsive_config.itemsTabletSmall;
      }

      if ($(this).attr("data-lg")) {
        responsive_config.itemsDesktopSmall = parseInt(
          $(this).attr("data-lg"),
          10
        );
      } else {
        responsive_config.itemsDesktopSmall = responsive_config.itemsTablet;
      }

      if ($(this).attr("data-xl")) {
        responsive_config.itemsDesktop = parseInt($(this).attr("data-xl"), 10);
      } else {
        responsive_config.itemsDesktop = responsive_config.itemsDesktopSmall;
      }

      if ($(this).attr("data-xxl")) {
        responsive_config.itemsDesktopLarge = parseInt(
          $(this).attr("data-xxl"),
          10
        );
      } else {
        responsive_config.itemsDesktopLarge = responsive_config.itemsDesktop;
      }

      let responsive = {
        0: { items: responsive_config.itemsXSmall },
        375: { items: responsive_config.itemsMobile },
        640: { items: responsive_config.itemsTabletSmall },
        768: { items: responsive_config.itemsTablet },
        1024: { items: responsive_config.itemsDesktopSmall },
        1200: { items: responsive_config.itemsDesktop },
        1600: { items: responsive_config.itemsDesktopLarge },
      };

      if ($.isEmptyObject(responsive_config) == false) {
        config.responsive = responsive;
      }

      owl.owlCarousel(config);
    });

    if ($(".related .products").length > 0) {
      $(".related .products").css("margin-top", "0");
    }

    if ($(".ced").length > 0) {
      $(".ced .products").removeClass("columns-4");
      $(".ced .products").addClass(
        "owl-carousel owl-theme menin-products mt-0"
      );
      $(".ced > h2, .related > h2").addClass("title-section");

      $(".ced .products").each(function () {
        $(this).owlCarousel({
          items: 1,
          navText: [
            '<i class="fal fa-chevron-left icon"></i>',
            '<i class="fal fa-chevron-right icon"></i>',
          ],
          autoplay: false,
          margin: 10,
          nav: true,
          dots: false,
          responsive: {
            0: {
              items: 2,
              margin: 5,
            },
            640: {
              items: 3,
            },
            992: {
              items: 4,
            },
            1300: {
              items: 5,
            },
          },
        });
      });
    }
  };

  /**
   *
   * Single product, input quantity
   *
   * */
  const inputQty = "input.qty",
    btnArrow = "button.arrow-qty";
  var timer = null,
    interval = 200;

  if (is_mobile()) {
    $("body").on("touchstart", btnArrow, function () {
      btnDown($(this));
    });
    $("body").on("touchend", btnArrow, function () {
      btnUp();
    });
  } else {
    $("body").on("mousedown", btnArrow, function () {
      btnDown($(this));
    });
    $("body").on("mouseup mouseleave", btnArrow, function () {
      btnUp();
    });
  }

  function btnDown(elem) {
    qntClick(elem);
    if (timer !== null) return;
    timer = setInterval(function () {
      qntClick(elem);
    }, interval);
  }

  function btnUp() {
    clearInterval(timer);
    timer = null;
    // in Cart
    let btnUpdate = $("[name='update_cart']");
    if (btnUpdate.length) {
      btnUpdate.data("disabled", "false");
      btnUpdate.removeAttr("disabled");

      setTimeout(() => {
        btnUpdate.trigger("click");
      }, 2500);
    }
  }

  function qntClick(elem) {
    const parentBtn = elem.parent();
    const $input = parentBtn.find(inputQty);
    const step = fixFormat($input.attr('step'));

    // Valor na input
    var num = $input.val();
    // Verifica se existe casa decimal e a input permite numero flutuante
    var hasDecimal = (num.indexOf(".") == -1) ? false : true;
    // Transforma em number a string
    num = Number(num);

    if ($input.val() === "") $input.val(step);

    if (elem.hasClass("acrescimo")) {
      if (num <= 0) {
        $input.val(hasDecimal ? parseFloat(step).toFixed(3) : (step));
      } else {
        $input.val(hasDecimal ? parseFloat(num + step).toFixed(3) : parseInt(num + step));
      }
    } else if (num > step) {
      $input.val(hasDecimal ? parseFloat(num - step).toFixed(3) : parseInt(num - step));
    }
  }

  function fixFormat(value) {
    if (value.indexOf(".") == -1) {
      return parseInt(value);
    } else {
      return parseFloat(value);
    }
  }

  /**
   * Disable Right-Click in live production
   */
  disableRightClick = () => {
    if (window.location.host !== "localhost") {
      window.addEventListener(
        "contextmenu",
        function (e) {
          console.log("Clique direito desabilitado.");

          e.preventDefault();
        },
        false
      );
    }
  };

  maskInput();
  smoothClick();
  owlCarousel();
  //disableRightClick();
});
