function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

(function () {
  var rootURL = "{{rootUrl}}";

  var containers = Array.from(document.querySelectorAll("[class^=codestepper]"));
  if (!containers.length) {
    return;
  }

  var _iteratorNormalCompletion = true;
  var _didIteratorError = false;
  var _iteratorError = undefined;

  try {
    for (var _iterator = containers[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
      var element = _step.value;

      var schemaId = element.className.split("-")[2];
      if (!schemaId) {
        continue;
      }
      element.innerHTML = "<div class=\"code-stepper\" data-schema-url=\"" + rootURL + "/public/codestepper-files/" + schemaId + "\"></div>";
    }
  } catch (err) {
    _didIteratorError = true;
    _iteratorError = err;
  } finally {
    try {
      if (!_iteratorNormalCompletion && _iterator.return) {
        _iterator.return();
      }
    } finally {
      if (_didIteratorError) {
        throw _iteratorError;
      }
    }
  }

  fetch(rootURL + "/api/init").then(function (res) {
    return res.json();
  }).then(loadScriptsAndStyles).catch(console.log);

  var ids = [];
  function loadScriptsAndStyles(_ref) {
    var scripts = _ref.scripts,
        styles = _ref.styles;
    var _iteratorNormalCompletion2 = true;
    var _didIteratorError2 = false;
    var _iteratorError2 = undefined;

    try {
      for (var _iterator2 = scripts[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
        var scriptUrl = _step2.value;

        ids.push(loadScript(rootURL + scriptUrl));
      }
    } catch (err) {
      _didIteratorError2 = true;
      _iteratorError2 = err;
    } finally {
      try {
        if (!_iteratorNormalCompletion2 && _iterator2.return) {
          _iterator2.return();
        }
      } finally {
        if (_didIteratorError2) {
          throw _iteratorError2;
        }
      }
    }

    var prs = styles.map(function (styleUrl) {
      return loadStyle(rootURL + "/" + styleUrl);
    });
    Promise.all(prs).then(function (styleIds) {
      ids.push.apply(ids, _toConsumableArray(styleIds));
    });
  }

  function loadScript(scriptUrl) {
    var script = document.createElement("script");
    var id = uuidv4();
    script.id = id;
    script.src = "" + scriptUrl;
    script.async = true;
    document.body.appendChild(script);
    return id;
  }

  function loadStyle(styleUrl) {
    return new Promise(function (res) {
      var id = uuidv4();
      var head = document.getElementsByTagName("head")[0];
      var link = document.createElement("link");
      link.id = id;
      link.rel = "stylesheet";
      link.type = "text/css";
      link.href = "" + styleUrl;
      link.media = "all";
      head.appendChild(link);

      if ("onload" in link) {
        link.onload = function () {
          res(id);
        };
      } else {
        res(id);
      }
    });
  }

  function uuidv4() {
    return ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, function (c) {
      return (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16);
    });
  }
})();