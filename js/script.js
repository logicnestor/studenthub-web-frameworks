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

// ==========================================
// Student Registration Validation
// Practical 5
// ==========================================

const registrationForm =
    document.querySelector("#registration-form");


if (registrationForm) {

    const nameInput =
        document.querySelector("#name");

    const emailInput =
        document.querySelector("#email");

    const mobileInput =
        document.querySelector("#mobile");

    const passwordInput =
        document.querySelector("#password");

    const confirmPasswordInput =
        document.querySelector("#confirm-password");

    const courseInput =
        document.querySelector("#course");

    const yearInput =
        document.querySelector("#year");

    const termsInput =
        document.querySelector("#terms");

    const formMessage =
        document.querySelector("#form-message");


    const nameRegex =
        /^[A-Za-z ]{2,50}$/;


    const emailRegex =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


    const mobileRegex =
        /^[6-9][0-9]{9}$/;


    function showError(input, errorId, message) {

        const errorElement =
            document.querySelector(`#${errorId}`);


        input.classList.remove("valid");

        input.classList.add("invalid");


        if (errorElement) {

            errorElement.textContent = message;

        }

    }


    function showValid(input, errorId) {

        const errorElement =
            document.querySelector(`#${errorId}`);


        input.classList.remove("invalid");

        input.classList.add("valid");


        if (errorElement) {

            errorElement.textContent = "";

        }

    }


    function validateName() {

        const name =
            nameInput.value.trim();


        if (name === "") {

            showError(
                nameInput,
                "name-error",
                "Name is required."
            );

            return false;

        }


        if (!nameRegex.test(name)) {

            showError(
                nameInput,
                "name-error",
                "Enter a valid name using letters and spaces only."
            );

            return false;

        }


        showValid(nameInput, "name-error");

        return true;

    }


    function validateEmail() {

        const email =
            emailInput.value.trim();


        if (email === "") {

            showError(
                emailInput,
                "email-error",
                "Email address is required."
            );

            return false;

        }


        if (!emailRegex.test(email)) {

            showError(
                emailInput,
                "email-error",
                "Enter a valid email address."
            );

            return false;

        }


        showValid(emailInput, "email-error");

        return true;

    }


    function validateMobile() {

        const mobile =
            mobileInput.value.trim();


        if (mobile === "") {

            showError(
                mobileInput,
                "mobile-error",
                "Mobile number is required."
            );

            return false;

        }


        if (!mobileRegex.test(mobile)) {

            showError(
                mobileInput,
                "mobile-error",
                "Enter a valid 10-digit Indian mobile number."
            );

            return false;

        }


        showValid(mobileInput, "mobile-error");

        return true;

    }


    function getPasswordStrength(password) {

        let score = 0;


        if (password.length >= 8) {

            score++;

        }


        if (/[A-Z]/.test(password)) {

            score++;

        }


        if (/[a-z]/.test(password)) {

            score++;

        }


        if (/[0-9]/.test(password)) {

            score++;

        }


        if (/[^A-Za-z0-9]/.test(password)) {

            score++;

        }


        return score;

    }


    function updatePasswordStrength() {

        const password =
            passwordInput.value;

        const strengthText =
            document.querySelector("#strength-text");


        if (!strengthText) {

            return;

        }


        const score =
            getPasswordStrength(password);


        if (password === "") {

            strengthText.textContent =
                "Not entered";

            return;

        }


        if (score <= 2) {

            strengthText.textContent =
                "Weak";

        } else if (score <= 4) {

            strengthText.textContent =
                "Medium";

        } else {

            strengthText.textContent =
                "Strong";

        }

    }


    function validatePassword() {

        const password =
            passwordInput.value;


        if (password === "") {

            showError(
                passwordInput,
                "password-error",
                "Password is required."
            );

            return false;

        }


        if (password.length < 8) {

            showError(
                passwordInput,
                "password-error",
                "Password must contain at least 8 characters."
            );

            return false;

        }


        if (!/[A-Z]/.test(password)) {

            showError(
                passwordInput,
                "password-error",
                "Password must contain at least one uppercase letter."
            );

            return false;

        }


        if (!/[a-z]/.test(password)) {

            showError(
                passwordInput,
                "password-error",
                "Password must contain at least one lowercase letter."
            );

            return false;

        }


        if (!/[0-9]/.test(password)) {

            showError(
                passwordInput,
                "password-error",
                "Password must contain at least one number."
            );

            return false;

        }


        if (!/[^A-Za-z0-9]/.test(password)) {

            showError(
                passwordInput,
                "password-error",
                "Password must contain at least one special character."
            );

            return false;

        }


        showValid(passwordInput, "password-error");

        return true;

    }


    function validateConfirmPassword() {

        const password =
            passwordInput.value;

        const confirmPassword =
            confirmPasswordInput.value;


        if (confirmPassword === "") {

            showError(
                confirmPasswordInput,
                "confirm-password-error",
                "Please confirm your password."
            );

            return false;

        }


        if (password !== confirmPassword) {

            showError(
                confirmPasswordInput,
                "confirm-password-error",
                "Passwords do not match."
            );

            return false;

        }


        showValid(
            confirmPasswordInput,
            "confirm-password-error"
        );

        return true;

    }


    function validateSelect(
        input,
        errorId,
        fieldName
    ) {

        if (input.value === "") {

            showError(
                input,
                errorId,
                `Please select ${fieldName}.`
            );

            return false;

        }


        showValid(input, errorId);

        return true;

    }


    function validateGender() {

        const gender =
            document.querySelector(
                'input[name="gender"]:checked'
            );

        const errorElement =
            document.querySelector("#gender-error");


        if (!gender) {

            if (errorElement) {

                errorElement.textContent =
                    "Please select your gender.";

            }

            return false;

        }


        if (errorElement) {

            errorElement.textContent = "";

        }

        return true;

    }


    function validateTerms() {

        const errorElement =
            document.querySelector("#terms-error");


        if (!termsInput.checked) {

            if (errorElement) {

                errorElement.textContent =
                    "You must accept the Terms and Conditions.";

            }

            return false;

        }


        if (errorElement) {

            errorElement.textContent = "";

        }

        return true;

    }


    passwordInput.addEventListener(
        "input",
        function () {

            updatePasswordStrength();

            validatePassword();

        }
    );


    confirmPasswordInput.addEventListener(
        "input",
        validateConfirmPassword
    );


    nameInput.addEventListener(
        "input",
        validateName
    );


    emailInput.addEventListener(
        "input",
        validateEmail
    );


    mobileInput.addEventListener(
        "input",
        validateMobile
    );


    courseInput.addEventListener(
        "change",
        function () {

            validateSelect(
                courseInput,
                "course-error",
                "a course"
            );

        }
    );


    yearInput.addEventListener(
        "change",
        function () {

            validateSelect(
                yearInput,
                "year-error",
                "a year"
            );

        }
    );


    document
        .querySelectorAll(
            'input[name="gender"]'
        )
        .forEach(function (radio) {

            radio.addEventListener(
                "change",
                validateGender
            );

        });


    termsInput.addEventListener(
        "change",
        validateTerms
    );


    registrationForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            const isNameValid =
                validateName();

            const isEmailValid =
                validateEmail();

            const isMobileValid =
                validateMobile();

            const isPasswordValid =
                validatePassword();

            const isConfirmPasswordValid =
                validateConfirmPassword();

            const isCourseValid =
                validateSelect(
                    courseInput,
                    "course-error",
                    "a course"
                );

            const isYearValid =
                validateSelect(
                    yearInput,
                    "year-error",
                    "a year"
                );

            const isGenderValid =
                validateGender();

            const isTermsValid =
                validateTerms();


            const isFormValid =
                isNameValid &&
                isEmailValid &&
                isMobileValid &&
                isPasswordValid &&
                isConfirmPasswordValid &&
                isCourseValid &&
                isYearValid &&
                isGenderValid &&
                isTermsValid;


            if (!isFormValid) {

                formMessage.textContent =
                    "Please correct the errors above.";

                formMessage.className =
                    "form-message error";

                return;

            }


            formMessage.textContent =
                "Registration successful!";

            formMessage.className =
                "form-message success";

        }
    );

}