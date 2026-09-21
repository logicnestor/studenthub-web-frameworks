// ==========================================
// StudentHub Events
// Practical 6
// Fetch API + Search + Filter + Sort + Pagination
// ==========================================

const eventsContainer =
    document.querySelector("#events-container");

if (eventsContainer) {

    const searchInput =
        document.querySelector("#event-search");

    const categoryFilter =
        document.querySelector("#event-category");

    const sortSelect =
        document.querySelector("#event-sort");

    const loadingMessage =
        document.querySelector("#events-loading");

    const errorMessage =
        document.querySelector("#events-error");

    const paginationContainer =
        document.querySelector("#events-pagination");


    let events = [];
    let filteredEvents = [];

    let currentPage = 1;

    const eventsPerPage = 6;


    // ==========================================
    // Fetch Events
    // ==========================================

    async function fetchEvents() {

        try {

            loadingMessage.hidden = false;
            errorMessage.hidden = true;

            const response =
                await fetch("../data/events.json");


            if (!response.ok) {
                throw new Error(
                    "Unable to load event data."
                );
            }


            events = await response.json();

            filteredEvents = [...events];

            renderEvents();

        } catch (error) {

            loadingMessage.hidden = true;

            errorMessage.textContent =
                "Sorry, events could not be loaded. Please try again later.";

            errorMessage.hidden = false;

            console.error(
                "Events Fetch Error:",
                error
            );

        }
    }


    // ==========================================
    // Filter Events
    // ==========================================

    function filterEvents() {

        const searchTerm =
            searchInput.value
                .trim()
                .toLowerCase();

        const selectedCategory =
            categoryFilter.value;


        filteredEvents = events.filter(function (event) {

            const matchesSearch =
                event.title
                    .toLowerCase()
                    .includes(searchTerm)

                ||

                event.location
                    .toLowerCase()
                    .includes(searchTerm)

                ||

                event.description
                    .toLowerCase()
                    .includes(searchTerm);


            const matchesCategory =
                selectedCategory === "all"
                ||
                event.category === selectedCategory;


            return matchesSearch &&
                   matchesCategory;

        });


        currentPage = 1;

        sortEvents();

        renderEvents();
    }


    // ==========================================
    // Sort Events
    // ==========================================

    function sortEvents() {

        const sortValue =
            sortSelect.value;


        filteredEvents.sort(function (a, b) {

            if (sortValue === "date-asc") {

                return new Date(a.date) -
                       new Date(b.date);

            }


            if (sortValue === "date-desc") {

                return new Date(b.date) -
                       new Date(a.date);

            }


            if (sortValue === "title-asc") {

                return a.title.localeCompare(
                    b.title
                );

            }


            if (sortValue === "title-desc") {

                return b.title.localeCompare(
                    a.title
                );

            }

        });

    }


    // ==========================================
    // Render Events
    // ==========================================

    function renderEvents() {

        loadingMessage.hidden = true;

        eventsContainer.innerHTML = "";


        if (filteredEvents.length === 0) {

            eventsContainer.innerHTML = `
                <p class="no-results">
                    No events found matching your search.
                </p>
            `;

            paginationContainer.innerHTML = "";

            return;
        }


        const startIndex =
            (currentPage - 1) *
            eventsPerPage;


        const endIndex =
            startIndex +
            eventsPerPage;


        const pageEvents =
            filteredEvents.slice(
                startIndex,
                endIndex
            );


        pageEvents.forEach(function (event) {

            const article =
                document.createElement("article");

            article.className =
                "event-card";


            const formattedDate =
                new Date(event.date)
                    .toLocaleDateString(
                        "en-IN",
                        {
                            day: "numeric",
                            month: "long",
                            year: "numeric"
                        }
                    );


            article.innerHTML = `
                <div class="event-card-content">

                    <span class="event-category">
                        ${event.category}
                    </span>

                    <h3>
                        ${event.title}
                    </h3>

                    <p>
                        ${event.description}
                    </p>

                    <p class="event-date">
                        <strong>Date:</strong>
                        ${formattedDate}
                    </p>

                    <p class="event-location">
                        <strong>Location:</strong>
                        ${event.location}
                    </p>

                </div>
            `;


            eventsContainer.appendChild(article);

        });


        renderPagination();

    }


    // ==========================================
    // Pagination
    // ==========================================

    function renderPagination() {

        paginationContainer.innerHTML = "";


        const totalPages =
            Math.ceil(
                filteredEvents.length /
                eventsPerPage
            );


        if (totalPages <= 1) {
            return;
        }


        // Previous button

        const previousButton =
            document.createElement("button");

        previousButton.textContent =
            "Previous";

        previousButton.type =
            "button";

        previousButton.disabled =
            currentPage === 1;


        previousButton.addEventListener(
            "click",
            function () {

                if (currentPage > 1) {

                    currentPage--;

                    renderEvents();

                }

            }
        );


        paginationContainer.appendChild(
            previousButton
        );


        // Page buttons

        for (
            let page = 1;
            page <= totalPages;
            page++
        ) {

            const pageButton =
                document.createElement("button");


            pageButton.textContent =
                page;

            pageButton.type =
                "button";


            pageButton.setAttribute(
                "aria-label",
                `Go to page ${page}`
            );


            if (page === currentPage) {

                pageButton.classList.add(
                    "active"
                );

                pageButton.setAttribute(
                    "aria-current",
                    "page"
                );

            }


            pageButton.addEventListener(
                "click",
                function () {

                    currentPage = page;

                    renderEvents();

                }
            );


            paginationContainer.appendChild(
                pageButton
            );

        }


        // Next button

        const nextButton =
            document.createElement("button");

        nextButton.textContent =
            "Next";

        nextButton.type =
            "button";

        nextButton.disabled =
            currentPage === totalPages;


        nextButton.addEventListener(
            "click",
            function () {

                if (currentPage < totalPages) {

                    currentPage++;

                    renderEvents();

                }

            }
        );


        paginationContainer.appendChild(
            nextButton
        );

    }


    // ==========================================
    // Event Listeners
    // ==========================================

    searchInput.addEventListener(
        "input",
        filterEvents
    );


    categoryFilter.addEventListener(
        "change",
        filterEvents
    );


    sortSelect.addEventListener(
        "change",
        function () {

            sortEvents();

            currentPage = 1;

            renderEvents();

        }
    );


    // ==========================================
    // Start Application
    // ==========================================

    fetchEvents();

}