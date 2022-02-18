var headerTop = '#header-main',
  navMenu = '#menu',
  navCart = '#shoppingbag',
  childAnchor = '.hasChild > a',
  $ = jQuery;

function menuMobile() {

  if (document.body.clientWidth <= 991) {
    // sidebar Menu
    new Mmenu(document.querySelector(navMenu), {
      // wrappers: ["bootstrap"],
      counters: true,
      offCanvas: true,
      searchfield: {
        placeholder: 'Busque por categorias',
        panel: true,
        showSubPanels: false,
        cancel: true,
        noResults: 'Nenhum resultado encontrado.'
      },
      navbars: [{
        position: 'top',
        content: ["searchfield"]
      }],
      extensions: [
        "fx-menu-slide", // Slide effect
        "pagedim-black", // Dim out the page to black
        "shadow-panels", // Shadow left swipe
        "theme-white",
        // "border-none"
      ],
      setSelected: {
        hover: true
      }
    }, {
      searchfield: {
        clear: true
      }
    });

    $(navMenu + ' ul').removeClass('owl-carousel theme-products');
  }

  // fixed header
  new Mhead(headerTop, {
    unpin: 200
  });

  // Cart menu
  new Mmenu(document.querySelector(navCart), {
    extensions: ["position-right", "fx-menu-slide", "pagedim-black", "theme-white"],
    navbar: {
      title: "Meu carrinho"
    }
  });
}

function menuDesktop() {
  if (document.body.clientWidth >= 992) {
    $(navMenu + '> ul').addClass('menu-desktop');

    // Add arrow element to the parent li items and chide its child uls
    $(navMenu).find('> ul > li ul').each(function () {
      var sub_ul = $(this),
        parent_a = sub_ul.prev('a'),
        parent_li = parent_a.parent('li').first();

      if (!parent_li.hasClass('hasChild'))
        parent_a.append('<i class="fas fa-sort-down"></i>');

      parent_li.addClass('hasChild');
      parent_a.attr('href', 'javascript:void(0)');
    });

    // Open submenu
    $(navMenu + ' .hasChild > a').click(function (e) {
      e.preventDefault();
      parent_li = $(this).parent().first();
      let item_ul = parent_li.find('ul').first();
      item_ul.toggleClass('visible');

      // background
      if (!$(".block-content").length) {
        $('#page').append('<div class="block-content"></div>');
        $('.block-content').fadeIn();
      } else if ($(".block-content").is(':hidden') && item_ul.hasClass("visible")) {
        $('.block-content').fadeIn();
      } else {
        $('.block-content').fadeOut();
      }
    });

    //Click on background
    $(document).on('click', '.block-content', function(e) {
      e.preventDefault();
      $(this).fadeOut();

      if ($('#menu ul').hasClass('visible'))
        $('#menu ul').removeClass('visible');
    });

  } else {
    $(navMenu + '> ul').removeClass('menu-desktop');

    if ($(".block-content").length) {
      $('.block-content').remove();
    }
  }
}

function blockTransparency() {
  if (document.body.clientWidth <= 992) {
    $('.block-content').fadeOut();
  }
}

document.addEventListener("DOMContentLoaded", menuMobile);
document.addEventListener("DOMContentLoaded", menuDesktop);

// window.addEventListener("resize", menuMobile);
// window.addEventListener("resize", menuDesktop);
window.addEventListener("resize", blockTransparency);