
document.querySelector("form").addEventListener("submit", function(e) {
    let email = document.querySelector("input[name='email']").value;
    if (!email.includes("@")) {
        alert("Invalid email");
        e.preventDefault();
    }
});

document.querySelectorAll("a").forEach(link => {
    link.addEventListener("click", function() {
        console.log("Navigating to:", this.textContent);
    });
});