(() => {
  const asda = (e) => {
    const container = document.getElementById("modal123-container");
    const schemaUrl = e.target.dataset.schemaUrl;
    const codeStepperScripts = JSON.parse(container.dataset.codeStepperScripts);
    const codeStepperStyles = JSON.parse(container.dataset.codeStepperStyles);
    const siteUrl = container.dataset.siteUrl;

    const encoded = content(schemaUrl, siteUrl, codeStepperScripts, codeStepperStyles);
    const highlighted = hljs.highlight(encoded, { language: 'html' }).value
    container.innerHTML = modalHTML(highlighted);

    const modal = new bootstrap.Modal(document.getElementById("websiteLinksModal"), {});
    modal.show();


    Array.from(document.getElementsByClassName("close-website-links-modal")).forEach(el => {
      el.addEventListener("click", () => {
        modal.hide();
        container.innerHTML = "";
      })
    });
  };

  Array.from(document.getElementsByClassName("open-website-links-modal")).forEach(el => {
    el.addEventListener("click", asda)
  });

  const modalHTML = (content) => `
    <div class="modal fade" id="websiteLinksModal" tabindex="-1" style="z-index:9999">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="z-index:9999">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title display-6">
              Paste this code to your website
            </h2>
            <button type="button" class="btn-close close-website-links-modal" ></button>
          </div>

          <div class="modal-body menlo-font">
            <pre class="hljs"><code class="html">
            ${content}
            </code></pre>
           
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary close-website-links-modal">
              Close
            </button>
            <button type="button" class="btn btn-success">Copy</button>
          </div>
        </div>
      </div>
    </div>
  `;

  const content = (schemaUrl, root, codestepperScrips, codestepperStyles) => `
  <!DOCTYPE html>
  <html lang="en">
  <head>
    
    <!-- Style to include -->
    ${codestepperStyles.map(s => `<link rel="stylesheet" href="${root}/public/${s.path}">`).join("\n  ")}

  </head>
  <body>

    <!-- CodeSteppers root element -->
    <div class="code-stepper" data-schema-url="${root}${schemaUrl}">
    </div>

    <!-- Scripts to include -->
    ${codestepperScrips.map(s => `<script src="${root}/public/${s.path}"></script>`).join("\n    ")}

  </body>
  </html>
  `;

  function htmlEncode(html) {
    return html.replace(/[&"'\<\>]/g, function (c) {
      switch (c) {
        case "&":
          return "&amp;";
        case "'":
          return "&#39;";
        case '"':
          return "&quot;";
        case "<":
          return "&lt;";
        default:
          return "&gt;";
      }
    });
  };

})();

