// Run this function when the page loads
window.addEventListener('DOMContentLoaded', () => {
    
    // 1. Generate Random Invoice Number and Date
    const date = new Date();
    document.getElementById('currentDate').innerText = date.toLocaleDateString();
    document.getElementById('invoiceNumber').innerText = 'INV-' + Math.floor(10000 + Math.random() * 90000);

    // 2. Get the data saved from the previous page
    const storedData = sessionStorage.getItem('orderDetails');

    if (!storedData) {
        // If user tries to access invoice.html directly without selecting a service
        document.getElementById('invoiceBody').innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; color: var(--danger);">
                    No service selected. Please go back and select a service.
                </td>
            </tr>
        `;
        return;
    }

    const order = JSON.parse(storedData);

    // 3. Populate the Table
    document.getElementById('invoiceBody').innerHTML = `
        <tr>
            <td><strong>${order.service}</strong><br><span style="color:var(--text-muted); font-size:12px;">Service & Maintenance</span></td>
            <td>${order.vehicle}</td>
            <td>${order.time}</td>
            <td class="text-right">${order.price}</td>
        </tr>
    `;

    // 4. Calculate Totals
    // Extract the numerical value from the price string (e.g., "Rs. 5,000" -> 5000)
    const priceNumber = parseFloat(order.price.replace(/[^0-9.-]+/g,""));
    
    const tax = 0; // Set tax to 0 for now, or change to priceNumber * 0.05 for 5%
    const total = priceNumber + tax;

    // Format back to Rs. format
    const formatPrice = (num) => "Rs. " + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    document.getElementById('subtotal').innerText = formatPrice(priceNumber);
    document.getElementById('tax').innerText = formatPrice(tax);
    document.getElementById('total').innerText = formatPrice(total);
});

// Payment Confirmation
function confirmPayment(bookingId) {
    // Disable the button so they don't click it twice
    const btn = document.querySelector('.btn-pay');
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
    btn.style.pointerEvents = 'none';

    // Send the booking ID to the PHP script to update the database
    fetch('confirm_payment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ booking_id: bookingId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Payment Successful! Your vehicle service is now booked.");
            window.location.href = "../services/services.php";
        } else {
            alert("Payment Failed: " + data.message);
            // Re-enable the button if it failed
            btn.innerHTML = '<i class="fa-brands fa-cc-visa"></i> Confirm & Pay Securely';
            btn.style.pointerEvents = 'auto';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Network error during payment.");
    });
}