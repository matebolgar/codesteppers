// (() => {

let plan;
let price;
let isVatFormOpen = false;
let modal;
let container;

let vatValues = {
  vatNumber: "",
  companyName: "",
  street: "",
  city: "",
  state: "",
  zip: "",
  country: "",
}


const init = e => {
  container = document.getElementById("modal123-container");
  const content = modalContent(plan, price, "");
  container.innerHTML = modalHTML(content);
  render();

  const modalEl = document.getElementById("websiteLinksModal");
  modal = new bootstrap.Modal(modalEl, {});
  modal.show();

  modalEl.addEventListener('hidden.bs.modal', function (event) {
    isVatFormOpen = false;
  })

  document.querySelector(".close-website-links-modal").onclick = () => {
    modal.hide();
    isVatFormOpen = false;
    container.innerHTML = "";
  }

};

Array.from(document.getElementsByClassName("open-website-links-modal")).forEach(el => {
  el.addEventListener("click", () => {
    plan = el.dataset.plan;
    price = el.dataset.price;
    init();
  });
});

function render() {
  const form = vatForm();
  document.querySelector(".modal-content").innerHTML = modalContent(plan, price, form);

  document.querySelectorAll("input").forEach(input => {
    input.oninput = (e) => {
      vatValues[e.target.name] = e.target.value;
    }
  })
}

function applyForm(e) {
  e.preventDefault();
  const isValid = Object.entries(vatValues).every(([k, v]) => v !== "");

  if (!isValid) {
    alert("All fields are required!");
    return;
  }

  isVatFormOpen = false;
  render();
}

const vatForm = () => `
    <div class="p-2 ${isVatFormOpen ? '' : 'd-none'}">
      <h1 class="lead text-dark text-center w-100 fw-bold">
        Enter VAT Number and Address
      </h1>

      <div class="mb-3">
        <input <input type="text" value="${vatValues.vatNumber}" name="vatNumber" class="form-control" placeholder="VAT Number">
      </div>
      
      <div class="mb-3">
        <input <input type="text" value="${vatValues.companyName}" name="companyName" class="form-control" placeholder="Company Name">
      </div>
      
      <div class="mb-3">
        <input <input type="text" value="${vatValues.country}" name="country" class="form-control" placeholder="Country">
      </div>

      <div class="mb-3">
        <input <input type="text" value="${vatValues.state}" name="state" class="form-control" placeholder="State">
      </div>
      
      <div class="mb-3">
      <input <input type="text" value="${vatValues.city}" name="city" class="form-control" placeholder="Town/City">
      </div>
      
      <div class="mb-3">
        <input <input type="text" value="${vatValues.zip}" name="zip" class="form-control" placeholder="ZIP Code">
      </div>
      
      <div class="mb-3">
        <input <input type="text" value="${vatValues.street}" name="street" class="form-control" placeholder="Street">
      </div> 
    </div>
  `;

const modalHTML = (modalContent) => `
    <div class="modal fade" id="websiteLinksModal" tabindex="-1" style="z-index:9999">
      <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable" style="z-index:9999">
        ${modalContent}
      </div>
    </div>
  `;

const modalContent = (plan, price, vatForm) => `
        <div class="modal-content">
          <div class="d-flex justify-content-end pt-2 pe-2">
            <button 
              type="button" 
              class="btn-close close-website-links-modal"
              onclick="modal.hide(); isVatFormOpen = false; container.innerHTML = '';"
              ></button>
          </div>
          <div class="modal-header p-1 pt-0 ${isVatFormOpen ? 'd-none' : ''}"">
            <h1 class="lead text-dark text-center w-100 fw-bold">
              Purchase CodeSteppers one year subscription <br/>
              <b>Plan: ${plan.toUpperCase()}</b>
            </h1>
          </div>

          <div class="modal-body px-3 text-center py-0">
            <p class="text-dark text-center py-2 lead fw-bold ${isVatFormOpen ? 'd-none' : ''}"">
              Your total is <span style="color: rgb(6, 198, 104);">${price} EUR</span> <span class="text-secondary" style="font-size: 0.6em">(inc. VAT)</span>
            </p>
            
            <form action="/upgrade-plan/${plan}" method="POST">
              <button class="w-100 btn pay-button ${isVatFormOpen ? 'd-none' : ''}"">
                Pay by Card »
              </button>

              ${vatForm}
            </form>
           ${isVatFormOpen ? `
              <button type="submit" class="w-100 btn pay-button" onclick="applyForm(window.event)">
                Apply
              </button> 
              <button class="w-100 btn btn-default" onclick="resetVatValues(); isVatFormOpen = false; render();">
                Cancel
              </button>
            ` : `
              <p 
                class="open-vat-form-btn small py-2 text-decoration-underline"
                onclick="isVatFormOpen = true; render();"
                style="color: rgb(6, 198, 104); cursor: pointer">
                Add VAT Number
              </p>
          `}

            <p class="text-dark text-center py-3" style="font-size: 0.9em">
              By clicking purchase you accept the 
              <a target="_blank" href="/terms-and-conditions">
                Terms & Conditions
              </a> and the 
              <a target="_blank" href="/privacy-policy">
              Privacy Policy</a>.
            </p>
          </div>
        </div>
  `;

function resetVatValues() {
  vatValues = Object.entries(vatValues).reduce((acc, [k, v]) => ({ ...acc, [k]: "" }), {});
}

// })();

