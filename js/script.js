 document.addEventListener("DOMContentLoaded", function () {
    const bookingForms = document.querySelectorAll("form");

    bookingForms.forEach(function (form) {
        const pickupDate = form.querySelector(
            'input[name="pickup_date"]'
        );

        const returnDate = form.querySelector(
            'input[name="return_date"]'
        );

        if (pickupDate && returnDate) {
            form.addEventListener("submit", function (event) {
                if (
                    !pickupDate.value ||
                    !returnDate.value ||
                    returnDate.value <= pickupDate.value
                ) {
                    event.preventDefault();

                    alert(
                        "Please select a return date after the pickup date."
                    );
                }
            });
        }
    });
});