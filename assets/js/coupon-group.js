"use strict";

document.addEventListener("DOMContentLoaded", function () {
  const wpFooter = document.getElementById("wpfooter");
  const couponGroupWrapper = document.getElementsByClassName("admin-cg-wrap");

  // Remove the Default Wp Footer form plugin's pages
  if (couponGroupWrapper.length !== 0 && wpFooter) {
    // Check if the element exists to avoid errors
    wpFooter.parentNode.removeChild(wpFooter);
  } else {
    wpFooter.parentNode.appendChild(wpFooter);
  }

  const couponGroupDeleteLinks = document.querySelectorAll(
    ".admin-cg-delete-link"
  );

  // Overview page overlay
  const overlay = document.getElementById("overlay");

  const confirmationBox = document.querySelector(
    ".admin-cg-delete-confirmation-box"
  );

  // Close box if the user hits escape
  document.addEventListener("keydown", function (event) {
    // Check if the pressed key is 'Escape'
    if (event.key === "Escape" && confirmationBox) {
      if (confirmationBox.style.display != "none") {
        overlay.style.display = "none";
        confirmationBox.style.display = "none";
      }
    }
  });

  document.addEventListener("click", function (event) {
    // Check if the clicked target is the modal itself
    if (
      confirmationBox &&
      !confirmationBox.contains(event.target) &&
      !event.target.classList.contains("admin-cg-delete-link")
    ) {
      overlay.style.display = "none";
      confirmationBox.style.display = "none";
    }
  });

  // Handle delete confirmation box
  if (couponGroupDeleteLinks.length !== 0) {
    for (let i = 0; i < couponGroupDeleteLinks.length; i++) {
      couponGroupDeleteLinks[i].addEventListener("click", function (event) {
        // Prevent the default action of the link (navigation) from occurring
        event.preventDefault();

        const deleteLink = event.currentTarget.href;

        // Access the data value
        const type = this.dataset.type;
        const name = this.dataset.name;

        const confirmationBoxTxtType = document.querySelector(
          "p .admin-cg-delete-item-type"
        );

        const confirmationBoxTxtName = document.querySelector(
          "p .admin-cg-delete-item-name"
        );

        const confirmationBoxButtons = document.querySelectorAll(
          ".admin-cg-delete-confirmation-box button"
        );

        // Diplay box and ovarlay
        overlay.style.display = "block";
        confirmationBox.style.display = "flex";
        confirmationBoxTxtType.textContent = type;
        confirmationBoxTxtName.textContent = name;

        // Confirmation box buttons click check
        for (let i = 0; i < confirmationBoxButtons.length; i++) {
          confirmationBoxButtons[i].addEventListener("click", function () {
            const action = this.dataset.action;
            if (action == "cancel") {
              overlay.style.display = "none";
              confirmationBox.style.display = "none";
            } else if (action == "delete") {
              // Delete the element
              window.location.href = deleteLink;
            }
          });
        }
      });
    }
  }
});
