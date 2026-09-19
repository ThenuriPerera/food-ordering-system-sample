// ========================================
// FOOD ORDERING SYSTEM - JAVASCRIPT
// STEP 06
// ========================================


// ========================================
// 1. QUANTITY FUNCTIONALITY
// ========================================

const quantityInput = document.getElementById("quantity");
const increaseButton = document.getElementById("increase");
const decreaseButton = document.getElementById("decrease");

if (quantityInput && increaseButton && decreaseButton) {

    increaseButton.addEventListener("click", function () {

        let quantity = Number(quantityInput.value);

        quantity++;

        quantityInput.value = quantity;
    });


    decreaseButton.addEventListener("click", function () {

        let quantity = Number(quantityInput.value);

        if (quantity > 1) {
            quantity--;
        }

        quantityInput.value = quantity;
    });
}


// ========================================
// 2. ADD TO CART
// ========================================

const addToCartButton = document.getElementById("addToCart");

if (addToCartButton) {

    addToCartButton.addEventListener("click", function () {

        // Get food information
        const foodName = "Classic Burger";
        const foodPrice = 850;

        // Get quantity
        const quantity = Number(quantityInput.value);

        // Create food object
        const food = {
            name: foodName,
            price: foodPrice,
            quantity: quantity
        };

        // Get existing cart
        let cart = JSON.parse(localStorage.getItem("cart")) || [];

        // Check whether food already exists
        const existingFood = cart.find(function (item) {
            return item.name === foodName;
        });

        if (existingFood) {

            // Increase existing quantity
            existingFood.quantity += quantity;

        } else {

            // Add new food
            cart.push(food);
        }

        // Save cart
        localStorage.setItem("cart", JSON.stringify(cart));

        // Update cart count
        updateCartCount();

        alert(foodName + " added to cart!");
    });
}


// ========================================
// 3. CART COUNT
// ========================================

function updateCartCount() {

    const cartCountElement = document.getElementById("cartCount");

    if (!cartCountElement) {
        return;
    }

    let cart = JSON.parse(localStorage.getItem("cart")) || [];

    let totalQuantity = 0;

    cart.forEach(function (item) {

        totalQuantity += item.quantity;

    });

    cartCountElement.textContent = totalQuantity;
}


// Update cart count when page loads
updateCartCount();


// ========================================
// 4. SEARCH FOOD
// ========================================

const searchInput = document.getElementById("searchInput");

if (searchInput) {

    searchInput.addEventListener("input", function () {

        const searchText = searchInput.value.toLowerCase();

        const foodCards = document.querySelectorAll(".food-card");

        foodCards.forEach(function (card) {

            const foodName = card
                .querySelector(".food-info h3")
                .textContent
                .toLowerCase();

            if (foodName.includes(searchText)) {

                card.style.display = "block";

            } else {

                card.style.display = "none";

            }

        });

    });
}


// ========================================
// 5. BASIC FORM VALIDATION
// ========================================

const registerForm = document.getElementById("registerForm");

if (registerForm) {

    registerForm.addEventListener("submit", function (event) {

        const name = document.getElementById("name").value.trim();
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();

        if (name === "") {

            alert("Please enter your name.");

            event.preventDefault();

            return;
        }

        if (email === "") {

            alert("Please enter your email.");

            event.preventDefault();

            return;
        }

        if (password === "") {

            alert("Please enter your password.");

            event.preventDefault();

            return;
        }

        alert("Form validation successful!");

    });
}