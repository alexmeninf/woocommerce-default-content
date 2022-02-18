/*!
 * Permite exibir somente um formulário por vez visivel. E outras implementações de UI
 * Version : 1.0.1
 */
if (undefined !== window.jQuery) {
  jQuery(function ($) {

    $(document).ready(function () {
      $('.login-register .woocommerce-notices-wrapper > div').removeClass('col-md-12');
      $('.login-register .woocommerce-notices-wrapper > div').addClass('col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4');

      // Shows the registration form if the message appears
      if (getParameterByName('f') == 'register') {
        showRegisterForm();
      }

      // Social buttons (requires plugin)
      if ($('.mo-openid-app-icons').length != 0) {
        $('.mo-openid-app-icons > p').attr('style', 'margin: 10px auto !important;text-align:center');
      
        $('.mo-openid-app-icons a').css({
          marginLeft: 0
        });
      }
    });

    $("#create-account-form").on("click", function (e) {
      e.preventDefault();
      showRegisterForm($(this));
    });

    $("#login-account-form").on("click", function (e) {
      e.preventDefault();
      showLoginForm($(this));
    });

    function showRegisterForm(dataElm = null) {
      let urlRegister = $('#create-account-form').data('link-register'),
      enableCustomRegister = $('#create-account-form').data('enable-custom-register');

      if (pageSlug() == "minha-conta" && enableCustomRegister != true) {
        let parmRedirect = getParameterByName('redirect_to');

        if (parmRedirect != null) {
          window.history.replaceState(null, null, "?f=register&redirect_to=" + parmRedirect);
        } else {
          window.history.replaceState(null, null, "?f=register");
        }

        $(".u-column1").hide();
        $(".u-column1").css('opacity', 0);
        $(".u-column2").show();
        $(".u-column2").css('opacity', 1);
      } else {
        dataElm !== null ? dataElm.addClass('redirecting') : '';
        window.location.href = urlRegister;
      }
    }

    function showLoginForm(dataElm = null) {
      if (pageSlug() == "minha-conta") {
        let parmRedirect = getParameterByName('redirect_to');

        if (parmRedirect != null) {
          window.history.replaceState(null, null, "?f=login&redirect_to=" + parmRedirect);
        } else {
          window.history.replaceState(null, null, "?f=login");
        }
        
        $(".u-column1").show();
        $(".u-column1").css('opacity', 1);
        $(".u-column2").hide();
        $(".u-column2").css('opacity', 0);
      } else {
        dataElm !== null ? dataElm.addClass('redirecting') : '';
        window.location.href = $('meta[name=urlajax]').attr('content') + '/minha-conta/?f=login';
      }
    }
  });
}

// Get paramenters in URL
function getParameterByName(name, url = window.location.href) {
  name = name.replace(/[\[\]]/g, '\\$&');
  var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
    results = regex.exec(url);
  if (!results) return null;
  if (!results[2]) return '';
  return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

function pageSlug() {
  var to = location.pathname.lastIndexOf('/');
  to = to == -1 ? location.pathname.length : to;
  url = location.pathname.substring(0, to);
  return url.substring(url.lastIndexOf('/') + 1);
}