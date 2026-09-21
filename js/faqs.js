// ==========================================
// StudentHub Dynamic FAQs
// Practical 6
// Fetch API + Search + Dynamic Rendering
// ==========================================

const faqsContainer =
    document.querySelector("#faqs-container");

if (faqsContainer) {

    const searchInput =
        document.querySelector("#faq-search");

    const loadingMessage =
        document.querySelector("#faqs-loading");

    const errorMessage =
        document.querySelector("#faqs-error");


    let faqs = [];


    // ==========================================
    // Fetch FAQs
    // ==========================================

    async function fetchFAQs() {

        try {

            loadingMessage.hidden = false;
            errorMessage.hidden = true;

            const response =
                await fetch("../data/faqs.json");


            if (!response.ok) {
                throw new Error(
                    "Unable to load FAQ data."
                );
            }


            faqs = await response.json();

            renderFAQs(faqs);

        } catch (error) {

            loadingMessage.hidden = true;

            errorMessage.textContent =
                "Sorry, FAQs could not be loaded. Please try again later.";

            errorMessage.hidden = false;

            console.error(
                "FAQs Fetch Error:",
                error
            );

        }
    }


    // ==========================================
    // Render FAQs
    // ==========================================

    function renderFAQs(faqList) {

        loadingMessage.hidden = true;

        faqsContainer.innerHTML = "";


        if (faqList.length === 0) {

            faqsContainer.innerHTML = `
                <p class="no-results">
                    No FAQs found matching your search.
                </p>
            `;

            return;
        }


        faqList.forEach(function (faq, index) {

            const article =
                document.createElement("article");

            article.className =
                "dynamic-faq-item";


            const questionId =
                `faq-question-${faq.id}`;

            const answerId =
                `faq-answer-${faq.id}`;


            article.innerHTML = `
                <button
                    type="button"
                    class="faq-question"
                    aria-expanded="false"
                    aria-controls="${answerId}">

                    <span>
                        ${faq.question}
                    </span>

                    <span
                        class="faq-icon"
                        aria-hidden="true">
                        +
                    </span>

                </button>


                <div
                    id="${answerId}"
                    class="faq-answer"
                    hidden>

                    <p>
                        ${faq.answer}
                    </p>

                </div>
            `;


            faqsContainer.appendChild(article);

        });


        addFAQListeners();

    }


    // ==========================================
    // FAQ Accordion
    // ==========================================

    function addFAQListeners() {

        const questions =
            faqsContainer.querySelectorAll(
                ".faq-question"
            );


        questions.forEach(function (question) {

            question.addEventListener(
                "click",
                function () {

                    const answerId =
                        question.getAttribute(
                            "aria-controls"
                        );

                    const answer =
                        document.getElementById(
                            answerId
                        );

                    const icon =
                        question.querySelector(
                            ".faq-icon"
                        );


                    const isExpanded =
                        question.getAttribute(
                            "aria-expanded"
                        ) === "true";


                    question.setAttribute(
                        "aria-expanded",
                        !isExpanded
                    );


                    answer.hidden =
                        isExpanded;


                    if (icon) {

                        icon.textContent =
                            isExpanded ? "+" : "−";

                    }

                }
            );

        });

    }


    // ==========================================
    // Search FAQs
    // ==========================================

    function searchFAQs() {

        const searchTerm =
            searchInput.value
                .trim()
                .toLowerCase();


        const filteredFAQs =
            faqs.filter(function (faq) {

                return (
                    faq.question
                        .toLowerCase()
                        .includes(searchTerm)

                    ||

                    faq.answer
                        .toLowerCase()
                        .includes(searchTerm)
                );

            });


        renderFAQs(filteredFAQs);

    }


    // ==========================================
    // Search Event
    // ==========================================

    searchInput.addEventListener(
        "input",
        searchFAQs
    );


    // ==========================================
    // Start
    // ==========================================

    fetchFAQs();

}