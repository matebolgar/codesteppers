(() => {
  const container = document.getElementById("drag-and-drop-app");
  let state = {
    elemek: {
      elso: {
        id: "elso",
        x: container.offsetLeft,
        y: container.offsetTop,
      },
      masodik: {
        id: "masodik",
        x: container.offsetLeft + 20,
        y: container.offsetTop + 150,
      },
      harmadik: {
        id: "harmadik",
        x: container.offsetLeft + 40,
        y: container.offsetTop + 300,
      },
    },
    draggedId: "",
  };

  logItem();
  render();

  // 1. Készíts renderelő függvényt, ami megjeleníti a dobozt a state-ből kiolvasott adatok alapján
  function render() {
    // 2. A dobozokat úgy rajzold ki, hogy az element-nek a position style attribútuma "absolute", a
    //    left és a top attribútuma pedig a state-ből származó x és y érték legyen
    //    Emellett írd be data attribútumnak az id-t is.

    let dobozokHTML = "";
    for (let elem of Object.values(state.elemek)) {
      dobozokHTML += `
        <div
          class="box ${state.draggedId === elem.id ? "grabbed" : "not-grabbed"}"
          style="position: absolute; left: ${elem.x}px; top: ${elem.y}px;"
          onmousedown="dobozDragStart(window.event)"
          onmouseup="dobozDragEnd(window.event)"
          onmousemove="dobozMouseMove(window.event)"
          data-egyedi-azonosito="${elem.id}"
        >
          <div class="card-body">
            <h5 class="card-title display-4">${elem.id}</h5>
          </div>
        </div>
      `;
    }

    container.innerHTML = dobozokHTML;
  }

  // 3. A doboz mousedown eseményre reagálva írd be az adott doboz id-ját a state.draggedId kulcsa alá
  window.dobozDragStart = function (event) {
    const box = event.target.closest(".box");
    state.draggedId = box.dataset.egyediAzonosito;
    logItem();
    render();
  };

  // 4. A doboz mouseup eseményre reagálva módosítsd a state state.draggedId értékét null-ra
  window.dobozDragEnd = function () {
    state.draggedId = null;
    logItem();
    render();
  };

  /* 5. A doboz mousemove eseménykor vizsgáld meg, hogy a state.isDragged értéke true-e
  Amennyiben igen, írd be a state x és y kulcsa alá az egér aktuális x,y pozícióját */
  window.dobozMouseMove = function (event) {
    if (!state.draggedId) {
      return;
    }

    const box = event.target.closest(".box");
    if (!box) {
      return;
    }

    state.elemek[state.draggedId].x = document.documentElement.scrollLeft + event.clientX - box.offsetWidth / 2;
    state.elemek[state.draggedId].y = document.documentElement.scrollTop + event.clientY - box.offsetHeight / 2;
    logItem();
    render();
  };

  // 7. Az állapotváltozások után hívd meg a renderelő függvényt

  function logItem() {
    document.getElementById("log").innerHTML = `
    <div class="p-2 rounded bg-light">
      <h1 class="fw-bold">
        State<span class="text-danger">*</span>
      </h1>
      <pre class="fw-bold" style="font-family: monospace">${JSON.stringify(state, null, "\t")}</pre>

    </div>
    `;
  }
})();
