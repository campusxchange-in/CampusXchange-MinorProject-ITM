document.addEventListener("DOMContentLoaded", function () {


    /* =====================================================
       DASHBOARD DATE
    ====================================================== */

    const dateElement =
        document.getElementById("dashboardDate");


    if (dateElement) {

        const today =
            new Date();


        const options = {

            weekday: "long",

            day: "numeric",

            month: "long",

            year: "numeric"

        };


        dateElement.textContent =
            today.toLocaleDateString(
                "en-IN",
                options
            );

    }


});