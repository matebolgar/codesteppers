(() => {
  let pages = [];
  let selectedPageIndex = 0;
  // let isPending = false;

  windowLoaded();

  function windowLoaded() {
    // isPending = true;
    // render();
    fetch("https://kodbazis.hu/api/members")
      .then((res) => res.json())
      .then((members) => {
        let res = [];
        const numberOfPages = Math.ceil(members.length / 30);
        for (let i = 0; i < numberOfPages; i++) {
          res.push(members.splice(0, 30));
        }
        pages = res;
        render();
      })
      .catch(console.log);
    // .finally(() => {
    //   isPending = false;
    //   render();
    // });
  }

  function render() {
    // if (isPending) {
    //   document.getElementById("app-container").innerHTML = '<div class="spinner-border"></div>';
    //   return;
    // }

    document.getElementById("app-container").innerHTML = `
        <nav class="mb-2">
            <ul class="pagination" style="flex-wrap: wrap; justify-content: center;">
                ${pages
                  .map(
                    (page, index) => `
                    <li class="page-item ${index === selectedPageIndex ? "active" : ""}" style="cursor: pointer">
                        <span class="page-link" data-index="${index}">
                            ${index + 1}
                        </span>
                    </li>`
                  )
                  .join("")}
            </ul>
        </nav>
        <ul class="list-group">
            ${pages[selectedPageIndex]
              .map((member) => `<li class="list-group-item">${member.ParliamentaryName}</li>`)
              .join("")}
        </ul>
        `;

    document.querySelectorAll(".page-link").forEach((element) => {
      element.onclick = function (event) {
        selectedPageIndex = Number(event.target.dataset.index);
        render();
      };
    });
  }
})();
