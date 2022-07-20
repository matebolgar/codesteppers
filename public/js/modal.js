(() => {
  const asda = (e) => {
    const container = document.getElementById("modal123-container");

    const siteUrl = container.dataset.siteUrl;
    const schemaId = container.dataset.schemaId;

    const encoded = content(siteUrl, schemaId);
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
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" style="z-index:9999">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title display-6 text-dark">
              Paste this code to your website:
            </h2>
            <button type="button" class="btn-close close-website-links-modal" ></button>
          </div>

          <div class="modal-body menlo-font">
            <pre class="hljs"><code class="html">
            ${content}
            </code></pre>
           
          </div>
        </div>
      </div>
    </div>
  `;

  const content = (root, schemaId) => `
    <script src="${root}/platform.js" defer></script>
    <div class="codestepper-app-${schemaId}">
    </div>
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

