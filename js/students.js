// ==========================================
// StudentHub Student Profiles
// Practical 6
// Fetch + Search + Filter + Sort + Pagination
// ==========================================

const studentsContainer =
    document.querySelector("#students-container");

if (studentsContainer) {

    const searchInput =
        document.querySelector("#student-search");

    const courseFilter =
        document.querySelector("#student-course");

    const sortSelect =
        document.querySelector("#student-sort");

    const loadingMessage =
        document.querySelector("#students-loading");

    const errorMessage =
        document.querySelector("#students-error");

    const paginationContainer =
        document.querySelector("#students-pagination");


    let students = [];
    let filteredStudents = [];

    let currentPage = 1;

    const studentsPerPage = 6;


    // ==========================================
    // Fetch Student Data
    // ==========================================

    async function fetchStudents() {

        try {

            loadingMessage.hidden = false;
            errorMessage.hidden = true;

            const response =
                await fetch("../data/students.json");


            if (!response.ok) {
                throw new Error(
                    "Unable to load student data."
                );
            }


            students = await response.json();

            filteredStudents = [...students];

            renderStudents();

        } catch (error) {

            loadingMessage.hidden = true;

            errorMessage.textContent =
                "Sorry, student data could not be loaded. Please try again later.";

            errorMessage.hidden = false;

            console.error(
                "Students Fetch Error:",
                error
            );

        }
    }


    // ==========================================
    // Filter Students
    // ==========================================

    function filterStudents() {

        const searchTerm =
            searchInput.value
                .trim()
                .toLowerCase();

        const selectedCourse =
            courseFilter.value;


        filteredStudents =
            students.filter(function (student) {

                const matchesSearch =
                    student.name
                        .toLowerCase()
                        .includes(searchTerm)

                    ||

                    student.email
                        .toLowerCase()
                        .includes(searchTerm);


                const matchesCourse =
                    selectedCourse === "all"
                    ||
                    student.course === selectedCourse;


                return matchesSearch &&
                       matchesCourse;

            });


        currentPage = 1;

        sortStudents();

        renderStudents();

    }


    // ==========================================
    // Sort Students
    // ==========================================

    function sortStudents() {

        const sortValue =
            sortSelect.value;


        filteredStudents.sort(function (a, b) {

            if (sortValue === "name-asc") {

                return a.name.localeCompare(
                    b.name
                );

            }


            if (sortValue === "name-desc") {

                return b.name.localeCompare(
                    a.name
                );

            }


            if (sortValue === "year-asc") {

                return getYearNumber(a.year) -
                       getYearNumber(b.year);

            }


            if (sortValue === "year-desc") {

                return getYearNumber(b.year) -
                       getYearNumber(a.year);

            }

        });

    }


    // ==========================================
    // Convert Year Text to Number
    // ==========================================

    function getYearNumber(year) {

        const yearMap = {
            "First Year": 1,
            "Second Year": 2,
            "Third Year": 3,
            "Fourth Year": 4
        };

        return yearMap[year] || 0;

    }


    // ==========================================
    // Render Students
    // ==========================================

    function renderStudents() {

        loadingMessage.hidden = true;

        studentsContainer.innerHTML = "";


        if (filteredStudents.length === 0) {

            studentsContainer.innerHTML = `
                <p class="no-results">
                    No students found matching your search.
                </p>
            `;

            paginationContainer.innerHTML = "";

            return;
        }


        const startIndex =
            (currentPage - 1) *
            studentsPerPage;


        const endIndex =
            startIndex +
            studentsPerPage;


        const pageStudents =
            filteredStudents.slice(
                startIndex,
                endIndex
            );


        pageStudents.forEach(function (student) {

            const article =
                document.createElement("article");

            article.className =
                "student-card";


            article.innerHTML = `
                <div class="student-card-content">

                    <span class="student-id">
                        Student ID: ${student.id}
                    </span>

                    <h3>
                        ${student.name}
                    </h3>

                    <p>
                        <strong>Email:</strong>
                        ${student.email}
                    </p>

                    <p>
                        <strong>Course:</strong>
                        ${student.course}
                    </p>

                    <p>
                        <strong>Year:</strong>
                        ${student.year}
                    </p>

                </div>
            `;


            studentsContainer.appendChild(article);

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
                filteredStudents.length /
                studentsPerPage
            );


        if (totalPages <= 1) {
            return;
        }


        // Previous

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

                    renderStudents();

                }

            }
        );


        paginationContainer.appendChild(
            previousButton
        );


        // Page numbers

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

                    renderStudents();

                }
            );


            paginationContainer.appendChild(
                pageButton
            );

        }


        // Next

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

                    renderStudents();

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
        filterStudents
    );


    courseFilter.addEventListener(
        "change",
        filterStudents
    );


    sortSelect.addEventListener(
        "change",
        function () {

            sortStudents();

            currentPage = 1;

            renderStudents();

        }
    );


    // ==========================================
    // Start
    // ==========================================

    fetchStudents();

}