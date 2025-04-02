
document.addEventListener('DOMContentLoaded', function() {
    // Event listener for "Place Order" button
    const placeOrderBtn = document.querySelector('.place-order-btn');
    if (placeOrderBtn) {
        placeOrderBtn.addEventListener('click', function(event) {
            event.preventDefault();
            alert("Order placed successfully!");
            window.location.href = 'order_history.html';
        });
    }

    // Event listeners for "Add to Cart" buttons
    const addToCartButtons = document.querySelectorAll('.product-card .btn');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            alert("Item has been updated successfully");
            // Uncomment the next line if you want to redirect to the cart page
            // window.location.href = 'cart.html';
        });
    });
});

console.log("popup.js loaded"); // Add this line for debugging
