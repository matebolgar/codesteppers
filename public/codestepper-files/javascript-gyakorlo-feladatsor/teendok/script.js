(() => {
  let state = {
    teendok: [],
    isPending: false,
  };

  // 1. Kattintás eseményre köss be funkcionalitást
  document.getElementById("fetch-btn").addEventListener("click", () => {
    // 2. A esemény bekövetkezésekor állítsd a state isPending értékét true-ra
    state.isPending = true;
    render();

    // 3. Küldj AJAX kérést a beépített "fetch" függvény segítségével
    // 4. A válaszként kapott adatokat szűrd meg a "filter" függvénnyel
    fetch("https://jsonplaceholder.typicode.com/todos")
      .then((res) => res.json())
      .then((todos) => todos.filter((todo) => todo.completed))
      .then((todos) => {
        // 5. A megszűrt adatokat írd be a state teendok kulcsa alá
        state.teendok = todos;
        // 6. Ezután állítsd vissza az isPending-et false-ra
        state.isPending = false;
        render();
        setTimeout(() => {
          document.getElementById("todo-list").innerHTML = "<div class='text-center'>Újrainicializálás...</div>";
        }, 4000);
        setTimeout(() => {
          state.teendok = [];
          render();
        }, 5000);
      })
      .catch(console.log);
  });

  /* 7. Készíts egy renderelő függvényt, ami
  - Ha az isPending true, akkor egy "Betöltés folyamatban" feliratot ír ki
  - Ha az isPending false, akkor pedig kirajzolja az összes teendőt, 
    ami a state-ben van
*/
  function render() {
    if (state.isPending) {
      document.getElementById("todo-list").innerHTML = "Betöltés folyamatban...";
      return;
    }

    document.getElementById("todo-list").innerHTML = state.teendok
      .map((teendo, i) => `<span class="badge bg-primary me-1 mb-1">${i + 1}. ${teendo.title}</span>`)
      .join("");
  }

  // 8. Hívd meg a renderelő függvényt a 2. és az 4. pont után is
})();
