// ==========================================
// StudentHub JavaScript
// Practical 4
// ==========================================

console.log("StudentHub JavaScript loaded successfully.");

// ==========================================
// Hamburger Menu
// ==========================================

const menuToggle = document.querySelector("#menu-toggle");

const mainNavigation = document.querySelector("#main-navigation");


if (menuToggle && mainNavigation) {

    menuToggle.addEventListener("click", function () {

        mainNavigation.classList.toggle("active");


        const isOpen =
            mainNavigation.classList.contains("active");


        menuToggle.setAttribute(
            "aria-expanded",
            isOpen
        );


        menuToggle.setAttribute(
            "aria-label",
            isOpen
                ? "Close navigation menu"
                : "Open navigation menu"
        );


        menuToggle.textContent =
            isOpen ? "✕" : "☰";

    });

}

// ==========================================
// Light / Dark Theme Switcher
// ==========================================

const themeToggle =
    document.querySelector("#theme-toggle");


if (themeToggle) {

    const savedTheme =
        localStorage.getItem("studenthub-theme");


    if (savedTheme === "dark") {

        document.body.classList.add("dark-theme");

        themeToggle.textContent = "☀️";

        themeToggle.setAttribute(
            "aria-label",
            "Switch to light mode"
        );

    }


    themeToggle.addEventListener("click", function () {

        document.body.classList.toggle("dark-theme");


        const isDark =
            document.body.classList.contains("dark-theme");


        if (isDark) {

            localStorage.setItem(
                "studenthub-theme",
                "dark"
            );

            themeToggle.textContent = "☀️";

            themeToggle.setAttribute(
                "aria-label",
                "Switch to light mode"
            );

        } else {

            localStorage.setItem(
                "studenthub-theme",
                "light"
            );

            themeToggle.textContent = "🌙";

            themeToggle.setAttribute(
                "aria-label",
                "Switch to dark mode"
            );

        }

    });

}

// ==========================================
// Notification Banner
// ==========================================

const notificationBanner =
    document.querySelector("#notification-banner");

const notificationClose =
    document.querySelector("#notification-close");


if (notificationBanner && notificationClose) {

    notificationClose.addEventListener(
        "click",
        function () {

            notificationBanner.classList.add("hide");

        }
    );

}

// ==========================================
// Modal Popup
// ==========================================

const openModal =
    document.querySelector("#open-modal");

const closeModal =
    document.querySelector("#close-modal");

const modalCloseButton =
    document.querySelector("#modal-close-button");

const modal =
    document.querySelector("#studenthub-modal");


function showModal() {

    if (!modal) {
        return;
    }

    modal.classList.add("active");

    modal.setAttribute(
        "aria-hidden",
        "false"
    );

}


function hideModal() {

    if (!modal) {
        return;
    }

    modal.classList.remove("active");

    modal.setAttribute(
        "aria-hidden",
        "true"
    );

}


if (openModal) {

    openModal.addEventListener(
        "click",
        showModal
    );

}


if (closeModal) {

    closeModal.addEventListener(
        "click",
        hideModal
    );

}


if (modalCloseButton) {

    modalCloseButton.addEventListener(
        "click",
        hideModal
    );

}

// Close modal when clicking outside the content

if (modal) {

    modal.addEventListener(
        "click",
        function (event) {

            if (event.target === modal) {

                hideModal();

            }

        }
    );

}

// Close modal with Escape key

document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key === "Escape" &&
            modal &&
            modal.classList.contains("active")
        ) {

            hideModal();

        }

    }
);

// ==========================================
// FAQ Accordion
// ==========================================

const faqQuestions =
    document.querySelectorAll(".faq-question");


faqQuestions.forEach(function (question) {

    question.addEventListener(
        "click",
        function () {

            const answerId =
                question.getAttribute(
                    "aria-controls"
                );

            const answer =
                document.getElementById(answerId);

            const icon =
                question.querySelector(".faq-icon");


            if (!answer) {
                return;
            }


            const isExpanded =
                question.getAttribute(
                    "aria-expanded"
                ) === "true";


            question.setAttribute(
                "aria-expanded",
                !isExpanded
            );


            answer.hidden = isExpanded;


            if (icon) {

                icon.textContent =
                    isExpanded ? "+" : "−";

            }

        }
    );

});

// ==========================================
// Content Slider
// Practical 4
// ==========================================

const slides =
    document.querySelectorAll(".slide");

const sliderDots =
    document.querySelectorAll(".slider-dot");

const previousButton =
    document.querySelector("#slider-prev");

const nextButton =
    document.querySelector("#slider-next");


let currentSlide = 0;


function showSlide(slideNumber) {

    if (slides.length === 0) {
        return;
    }


    if (slideNumber >= slides.length) {

        currentSlide = 0;

    } else if (slideNumber < 0) {

        currentSlide = slides.length - 1;

    } else {

        currentSlide = slideNumber;

    }


    slides.forEach(function (slide, index) {

        slide.classList.toggle(
            "active",
            index === currentSlide
        );

    });


    sliderDots.forEach(function (dot, index) {

        const isActive =
            index === currentSlide;


        dot.classList.toggle(
            "active",
            isActive
        );


        if (isActive) {

            dot.setAttribute(
                "aria-current",
                "true"
            );

        } else {

            dot.removeAttribute(
                "aria-current"
            );

        }

    });

}

if (previousButton) {

    previousButton.addEventListener(
        "click",
        function () {

            showSlide(currentSlide - 1);

        }
    );

}


if (nextButton) {

    nextButton.addEventListener(
        "click",
        function () {

            showSlide(currentSlide + 1);

        }
    );

}

sliderDots.forEach(function (dot) {

    dot.addEventListener(
        "click",
        function () {

            const slideNumber =
                Number(
                    dot.getAttribute("data-slide")
                );


            showSlide(slideNumber);

        }
    );

});

if (slides.length > 0) {

    setInterval(function () {

        showSlide(currentSlide + 1);

    }, 5000);

}