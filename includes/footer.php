</div> <!-- End of content-wrapper -->
</div> <!-- End of content -->
</div> <!-- End of wrapper -->

<!-- Core Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

<!-- Dark Mode Logic -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("darkModeToggle");
    const themeIcon = document.getElementById("themeIcon");
    const body = document.body;

    if (toggleBtn && themeIcon) {
        // Hubi LocalStorage markuu page-ku soo load-garoobo
        if (localStorage.getItem("theme") === "dark") {
            body.classList.add("dark-mode");
            themeIcon.classList.remove("fa-moon");
            themeIcon.classList.add("fa-sun");
            toggleBtn.classList.replace("btn-light", "btn-dark");
        }

        // Action-ka dhacaya marka la riixo Batoonka Dark/Light
        toggleBtn.addEventListener("click", function () {
            body.classList.toggle("dark-mode");

            if (body.classList.contains("dark-mode")) {
                localStorage.setItem("theme", "dark");
                themeIcon.classList.remove("fa-moon");
                themeIcon.classList.add("fa-sun");
                toggleBtn.classList.replace("btn-light", "btn-dark");
            } else {
                localStorage.setItem("theme", "light");
                themeIcon.classList.remove("fa-sun");
                themeIcon.classList.add("fa-moon");
                toggleBtn.classList.replace("btn-dark", "btn-light");
            }
        });
    }
});
</script>

</body>
</html>