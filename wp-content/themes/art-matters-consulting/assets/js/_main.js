// Modified http://paulirish.com/2009/markup-based-unobtrusive-comprehensive-dom-ready-execution/
// Only fires on body class (working off strictly WordPress body_class)

var ExampleSite = {
  // All pages
  common: {
    init: function() {
      // JS here
        if (!('boxShadow' in document.body.style)) {
            document.body.setAttribute('class', 'noBoxShadow');
        }

        document.body.addEventListener("click", function(e) {
            var target = e.target;
            if (target.tagName === "INPUT" &&
                target.getAttribute('class').indexOf('liga') === -1) {
                target.select();
            }
        });

        (function() {
            var fontSize = document.getElementById('fontSize'),
                testDrive = document.getElementById('testDrive'),
                testText = document.getElementById('testText');
            function updateTest() {
                testDrive.innerHTML = testText.value || String.fromCharCode(160);
                if (window.icomoonLiga) {
                    window.icomoonLiga(testDrive);
                }
            }
            function updateSize() {
                testDrive.style.fontSize = fontSize.value + 'px';
            }
            fontSize.addEventListener('change', updateSize, false);
            testText.addEventListener('input', updateTest, false);
            testText.addEventListener('change', updateTest, false);
            updateSize();
        }());

    },
    finalize: function() { }
  },
  // Home page
  home: {
    init: function() {
      // JS here
    }
  },
  // About page
  about: {
    init: function() {
      // JS here
    }
  }
};

var UTIL = {
  fire: function(func, funcname, args) {
    var namespace = ExampleSite;
    funcname = (funcname === undefined) ? 'init' : funcname;
    if (func !== '' && namespace[func] && typeof namespace[func][funcname] === 'function') {
      namespace[func][funcname](args);
    }
  },
  loadEvents: function() {

    UTIL.fire('common');

    $.each(document.body.className.replace(/-/g, '_').split(/\s+/),function(i,classnm) {
      UTIL.fire(classnm);
    });

    UTIL.fire('common', 'finalize');
  }
};

$(document).ready(UTIL.loadEvents);
