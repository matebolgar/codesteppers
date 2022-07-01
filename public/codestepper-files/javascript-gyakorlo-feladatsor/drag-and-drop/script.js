(() => {
  let state = {
    x: undefined,
    y: undefined,
    isDragged: false,
  };

  const container = document.getElementById("drag-and-drop-app");
  state.x = container.offsetLeft;
  state.y = container.offsetTop;

  render();

  function render() {
    const doboz = `
      <div 
        onmousedown="window.dobozDragStart()"
        onmouseup="window.dobozDragEnd()"
        onmousemove="window.dobozMouseMove(window.event)"
        class="box ${state.isDragged ? "grabbed" : "not-grabbed"}" 
        style="width: 200px; position: absolute; left: ${state.x}px; top: ${state.y}px;"
      >
          <div class="card-body">
              <h5 class="card-title display-4"># húzd arrébb</h5>
          </div>
      </div>
    `;

    document.getElementById("drag-and-drop-app").innerHTML = doboz;
  }

  window.dobozDragStart = function () {
    state.isDragged = true;
    render();
  };

  window.dobozDragEnd = function () {
    state.isDragged = false;
    render();
  };

  window.dobozMouseMove = function (event) {
    if (state.isDragged) {
      const box = event.target.closest(".box");
      if (!box) {
        return;
      }
      state.x = document.documentElement.scrollLeft + event.clientX - box.offsetWidth / 2;
      state.y = document.documentElement.scrollTop + event.clientY - box.offsetHeight / 2;
      render();
    }
  };
})();
