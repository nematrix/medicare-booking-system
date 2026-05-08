// theme.js

function applyTheme(theme) {
    document.body.classList.remove("dark", "light");
    document.body.classList.add(theme);
    localStorage.setItem("theme", theme);
}

function toggleTheme() {
    let current = localStorage.getItem("theme") || "light";
    let newTheme = current === "dark" ? "light" : "dark";
    applyTheme(newTheme);
}

// run on every page load
document.addEventListener("DOMContentLoaded", function () {
    let savedTheme = localStorage.getItem("theme") || "light";
    applyTheme(savedTheme);
});